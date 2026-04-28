<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Video::with(['category']);

        if ($request->has('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', "%{$request->search}%")
                  ->orWhere('description', 'LIKE', "%{$request->search}%");
            });
        }

        $videos = $query->orderBy('created_at', 'desc')->paginate(10);

        // Agregar URL completa del thumbnail
        $videos->getCollection()->transform(function ($video) {
            if ($video->thumbnail_url) {
                $video->thumbnail_url = Storage::url($video->thumbnail_url);
            }
            return $video;
        });

        return response()->json([
            'success' => true,
            'data' => $videos
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'required|url',
            'thumbnail_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'duration' => 'nullable|integer',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:workshop,tutorial,webinar',
            'is_free' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        if ($request->hasFile('thumbnail_url')) {
            $path = $request->file('thumbnail_url')->store('videos', 'public');
            $validated['thumbnail_url'] = $path;
        }

        $validated['is_free'] = filter_var($request->is_free ?? true, FILTER_VALIDATE_BOOLEAN);
        $validated['is_published'] = filter_var($request->is_published ?? false, FILTER_VALIDATE_BOOLEAN);

        $video = Video::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Video creado exitosamente',
            'data' => $video
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $video = Video::with(['category'])->findOrFail($id);
        
        if ($video->thumbnail_url) {
            $video->thumbnail_url = Storage::url($video->thumbnail_url);
        }

        return response()->json([
            'success' => true,
            'data' => $video
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $video = Video::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'required|url',
            'thumbnail_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'duration' => 'nullable|integer',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:workshop,tutorial,webinar',
            'is_free' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        if ($request->hasFile('thumbnail_url')) {
            if ($video->thumbnail_url) {
                Storage::disk('public')->delete($video->thumbnail_url);
            }
            $path = $request->file('thumbnail_url')->store('videos', 'public');
            $validated['thumbnail_url'] = $path;
        }

        $validated['is_free'] = filter_var($request->is_free ?? $video->is_free, FILTER_VALIDATE_BOOLEAN);
        $validated['is_published'] = filter_var($request->is_published ?? $video->is_published, FILTER_VALIDATE_BOOLEAN);

        $video->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Video actualizado exitosamente',
            'data' => $video
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $video = Video::findOrFail($id);
        
        if ($video->thumbnail_url) {
            Storage::disk('public')->delete($video->thumbnail_url);
        }
        
        $video->delete();

        return response()->json([
            'success' => true,
            'message' => 'Video eliminado exitosamente'
        ]);
    }
    public function togglePublish($id)
    {
        $video = Video::findOrFail($id);
        $video->is_published = !$video->is_published;
        
        if ($video->is_published && !$video->published_at) {
            $video->published_at = now();
        }
        
        $video->save();

        return response()->json([
            'success' => true,
            'message' => $video->is_published ? 'Video publicado' : 'Video despublicado',
            'data' => $video
        ]);
    }

    public function getCategories()
    {
        $categories = Category::where('type', 'video')
            ->orWhereNull('type')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}
