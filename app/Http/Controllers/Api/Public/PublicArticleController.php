<?php
// app/Http/Controllers/Api/Public/PublicArticleController.php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['author', 'category'])
            ->where('is_published', true)
            ->where('published_at', '<=', now());
        
        if ($request->has('limit')) {
            $query->limit($request->limit);
        }
        
        if ($request->has('sort') && $request->sort === 'latest') {
            $query->orderBy('published_at', 'desc');
        }
        
        $articles = $query->get();
        
        // Agregar URL completa de la imagen y del avatar del autor
        $articles->transform(function ($article) {
            if ($article->featured_image) {
                $article->featured_image = Storage::url($article->featured_image);
            }
            if ($article->author && $article->author->avatar_url) {
                $article->author->avatar_url = Storage::url($article->author->avatar_url);
            }
            return $article;
        });
        
        return response()->json([
            'success' => true,
            'data' => $articles
        ]);
    }
    
    public function show($slug)
    {
        $article = Article::with(['author', 'category'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
        
        if ($article->featured_image) {
            $article->featured_image = Storage::url($article->featured_image);
        }
        
        // Incrementar vistas
        $article->increment('views');
        
        return response()->json([
            'success' => true,
            'data' => $article
        ]);
    }
}