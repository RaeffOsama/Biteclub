<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\RestaurantSuggestion;
use App\Models\Report;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'total_posts' => Post::count(),
            'total_restaurants' => Restaurant::count(),
            'total_reviews' => Review::count(),
            'pending_suggestions' => RestaurantSuggestion::where('status', 'pending')->count(),
            'pending_reports' => Report::where('status', 'pending')->count(),
        ];

        $recentUsers = User::latest()->take(5)->get();
        $recentPosts = Post::with(['user', 'restaurant'])->latest()->take(5)->get();
        $pendingSuggestions = RestaurantSuggestion::where('status', 'pending')->with('user')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentPosts', 'pendingSuggestions'));
    }
}
