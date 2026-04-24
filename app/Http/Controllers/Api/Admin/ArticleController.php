<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['author', 'category']);

        if ($request->has('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', "%{$request->search}%")
                  ->orWhere('excerpt', 'LIKE', "%{$request->search}%");
            });
        }

        $articles = $query->orderBy('created_at', 'desc')->paginate(10);

        // Agregar URL completa de la imagen destacada
        $articles->getCollection()->transform(function ($article) {
            if ($article->featured_image) {
                $article->featured_image = Storage::url($article->featured_image);
            }
            return $article;
        });

        return response()->json([
            'success' => true,
            'data' => $articles
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'nullable|exists:categories,id',
            'author_id' => 'nullable|exists:authors,id',
            'tags' => 'nullable|string', // Recibir como string
            'is_featured' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
        ]);

        // Manejar imagen destacada - guardar solo la ruta relativa
        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('articles', 'public');
            $validated['featured_image'] = $path; // Guardar ruta relativa, no URL completa
        }

        // Procesar tags - convertir string a array
        if ($request->has('tags')) {
            $tags = json_decode($request->tags, true);
            $validated['tags'] = is_array($tags) ? $tags : [];
        }

        // Asegurar booleanos
        $validated['is_featured'] = filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN);
        $validated['is_published'] = filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN);

        $article = Article::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Artículo creado exitosamente',
            'data' => $article
        ], 201);
    }

    public function show($id)
    {
        $article = Article::with(['author', 'category'])->findOrFail($id);
    
        if ($article->featured_image) {
            $article->featured_image = Storage::url($article->featured_image);
        }

        return response()->json([
            'success' => true,
            'data' => $article
        ]);
    }

    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'nullable|exists:categories,id',
            'author_id' => 'nullable|exists:authors,id',
            'tags' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
        ]);

        if ($request->hasFile('featured_image')) {
            if ($article->featured_image) {
                Storage::disk('public')->delete($article->featured_image);
            }
            $path = $request->file('featured_image')->store('articles', 'public');
            $validated['featured_image'] = $path;
        }

        if ($request->has('tags')) {
            $tags = json_decode($request->tags, true);
            $validated['tags'] = is_array($tags) ? $tags : [];
        }

        $validated['is_featured'] = filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN);
        $validated['is_published'] = filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN);

        $article->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Artículo actualizado exitosamente',
            'data' => $article
        ]);
    }

    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        
        if ($article->featured_image) {
            Storage::disk('public')->delete($article->featured_image);
        }
        
        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Artículo eliminado exitosamente'
        ]);
    }

    public function togglePublish($id)
    {
        $article = Article::findOrFail($id);
        $article->is_published = !$article->is_published;
        
        if ($article->is_published && !$article->published_at) {
            $article->published_at = now();
        }
        
        $article->save();

        return response()->json([
            'success' => true,
            'message' => $article->is_published ? 'Artículo publicado' : 'Artículo despublicado'
        ]);
    }

    public function toggleFeatured($id)
    {
        $article = Article::findOrFail($id);
        $article->is_featured = !$article->is_featured;
        $article->save();

        return response()->json([
            'success' => true,
            'message' => $article->is_featured ? 'Artículo destacado' : 'Artículo no destacado'
        ]);
    }

    public function getCategories()
    {
        $categories = Category::where('type', 'article')
            ->orWhereNull('type')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function getAuthors()
    {
        $authors = Author::orderBy('name')->get();

        // Agregar URL completa del avatar si existe
        $authors->transform(function ($author) {
            if ($author->avatar_url) {
                $author->avatar_url = Storage::url($author->avatar_url);
            }
            return $author;
        });

        return response()->json([
            'success' => true,
            'data' => $authors
        ]);
    }
}