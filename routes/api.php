<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ArticleController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\AuthorController;
use App\Http\Controllers\Api\Admin\VideoController;
use App\Http\Controllers\Api\Admin\PodcastController;
use App\Http\Controllers\Api\Admin\DownloadController;
use App\Http\Controllers\Api\Public\PublicArticleController;

// Rutas públicas
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando correctamente']);
});
Route::post('/login', [AuthController::class, 'login']);
Route::prefix("public")->group(function(){
    Route::get('/articles', [PublicArticleController::class, 'index']);
    Route::get('/articles/{slug}', [PublicArticleController::class, 'show']);
});



Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    // Rutas de administración (requieren ser admin)
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        Route::get('/authors', [AuthorController::class, 'index']);
        Route::get('/authors/{id}', [AuthorController::class, 'show']);
        Route::post('/authors', [AuthorController::class, 'store']);
        Route::put('/authors/{id}', [AuthorController::class, 'update']);
        Route::delete('/authors/{id}', [AuthorController::class, 'destroy']);
        Route::get('/articles', [ArticleController::class, 'index']);
        Route::get('/articles/categories', [ArticleController::class, 'getCategories']);
        Route::get('/articles/authors', [ArticleController::class, 'getAuthors']);
        Route::get('/articles/{id}', [ArticleController::class, 'show']);
        Route::post('/articles', [ArticleController::class, 'store']);
        Route::put('/articles/{id}', [ArticleController::class, 'update']);
        Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);
        Route::post('/articles/{id}/toggle-publish', [ArticleController::class, 'togglePublish']);
        Route::post('/articles/{id}/toggle-featured', [ArticleController::class, 'toggleFeatured']);
        Route::get('/videos', [VideoController::class, 'index']);
        Route::get('/videos/categories', [VideoController::class, 'getCategories']);
        Route::get('/videos/{id}', [VideoController::class, 'show']);
        Route::post('/videos', [VideoController::class, 'store']);
        Route::put('/videos/{id}', [VideoController::class, 'update']);
        Route::delete('/videos/{id}', [VideoController::class, 'destroy']);
        Route::post('/videos/{id}/toggle-publish', [VideoController::class, 'togglePublish']);
        Route::get('/podcasts', [PodcastController::class, 'index']);
        Route::get('/podcasts/categories', [PodcastController::class, 'getCategories']);
        Route::get('/podcasts/{id}', [PodcastController::class, 'show']);
        Route::post('/podcasts', [PodcastController::class, 'store']);
        Route::put('/podcasts/{id}', [PodcastController::class, 'update']);
        Route::delete('/podcasts/{id}', [PodcastController::class, 'destroy']);
        Route::post('/podcasts/{id}/toggle-publish', [PodcastController::class, 'togglePublish']);
        Route::get('/downloads', [DownloadController::class, 'index']);
        Route::get('/downloads/categories', [DownloadController::class, 'getCategories']);
        Route::get('/downloads/{id}', [DownloadController::class, 'show']);
        Route::post('/downloads', [DownloadController::class, 'store']);
        Route::put('/downloads/{id}', [DownloadController::class, 'update']);
        Route::delete('/downloads/{id}', [DownloadController::class, 'destroy']);
        Route::post('/downloads/{id}/toggle-publish', [DownloadController::class, 'togglePublish']);
        Route::post('/downloads/{id}/increment', [DownloadController::class, 'incrementDownloads']);
    });
});