<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Podcast;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PodcastController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
       $query = Podcast::with(['category']);

        if ($request->has('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $podcasts = $query->orderBy('created_at', 'desc')->paginate(10);

        // Agregar URL completa de la imagen
        $podcasts->getCollection()->transform(function ($podcast) {
            if ($podcast->cover_image) {
                $podcast->cover_image = Storage::url($podcast->cover_image);
            }
            return $podcast;
        });

        return response()->json([
            'success' => true,
            'data' => $podcasts
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
            'episode_number' => 'nullable|integer',
            'season_number' => 'nullable|integer',
            'audio_url' => 'required|url',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'duration' => 'nullable|integer',
            'category_id' => 'nullable|exists:categories,id',
            'guests' => 'nullable|string', // ← CAMBIADO: Recibir como string (igual que tags en Articles)
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        // Manejar cover_image
        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('podcasts', 'public');
            $validated['cover_image'] = $path;
        }

        // Procesar guests - convertir string a array (igual que tags en Articles)
        if ($request->has('guests')) {
            $guests = json_decode($request->guests, true);
            $validated['guests'] = is_array($guests) ? $guests : [];
        }

        // Convertir booleanos
        $validated['is_published'] = filter_var($request->is_published ?? false, FILTER_VALIDATE_BOOLEAN);

        $podcast = Podcast::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Podcast creado exitosamente',
            'data' => $podcast
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $podcast = Podcast::with(['category'])->findOrFail($id);
        
        // Asegurar que guests sea un array (igual que social_links en Author)
        if ($podcast->guests && is_string($podcast->guests)) {
            $podcast->guests = json_decode($podcast->guests, true);
        }

        if ($podcast->cover_image) {
            $podcast->cover_image = Storage::url($podcast->cover_image);
        }

        return response()->json([
            'success' => true,
            'data' => $podcast
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $podcast = Podcast::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'episode_number' => 'nullable|integer',
            'season_number' => 'nullable|integer',
            'audio_url' => 'required|url',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'duration' => 'nullable|integer',
            'category_id' => 'nullable|exists:categories,id',
            'guests' => 'nullable|string', // ← CAMBIADO: Recibir como string
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
        ]);

        if ($request->hasFile('cover_image')) {
            if ($podcast->cover_image) {
                Storage::disk('public')->delete($podcast->cover_image);
            }
            $path = $request->file('cover_image')->store('podcasts', 'public');
            $validated['cover_image'] = $path;
        }

        // Procesar guests - convertir string a array (igual que tags en Articles)
        if ($request->has('guests')) {
            $guests = json_decode($request->guests, true);
            $validated['guests'] = is_array($guests) ? $guests : [];
        }

        $validated['is_published'] = filter_var($request->is_published ?? $podcast->is_published, FILTER_VALIDATE_BOOLEAN);

        $podcast->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Podcast actualizado exitosamente',
            'data' => $podcast
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $podcast = Podcast::findOrFail($id);
        
        // Eliminar cover_image si existe
        if ($podcast->cover_image) {
            $path = str_replace('/storage/', '', $podcast->cover_image);
            Storage::disk('public')->delete($path);
        }
        
        $podcast->delete();

        return response()->json([
            'success' => true,
            'message' => 'Podcast eliminado exitosamente'
        ]);
    }

    public function togglePublish($id)
    {
        $podcast = Podcast::findOrFail($id);
        $podcast->is_published = !$podcast->is_published;
        
        if ($podcast->is_published && !$podcast->published_at) {
            $podcast->published_at = now();
        }
        
        $podcast->save();

        return response()->json([
            'success' => true,
            'message' => $podcast->is_published ? 'Podcast publicado' : 'Podcast despublicado',
            'data' => $podcast
        ]);
    }

    public function getCategories()
    {
        $categories = Category::where('type', 'podcast')
            ->orWhereNull('type')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}
