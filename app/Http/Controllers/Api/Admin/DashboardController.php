<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\Video;
use App\Models\Podcast;
use App\Models\Download;
use App\Models\Subscriber;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'articles' => Article::count(),
            'videos' => Video::count(),
            'podcasts' => Podcast::count(),
            'downloads' => Download::count(),
            'subscribers' => Subscriber::count(),
            'recent_articles' => Article::latest()->take(5)->get(),
            'recent_videos' => Video::latest()->take(5)->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
