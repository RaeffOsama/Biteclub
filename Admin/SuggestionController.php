<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantSuggestion;
use App\Models\Restaurant;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SuggestionController extends Controller
{
    public function index(): View
    {
        $suggestions = RestaurantSuggestion::with('user')
            ->latest()
            ->paginate(15);

        return view('admin.suggestions.index', compact('suggestions'));
    }

    public function approve(Request $request, RestaurantSuggestion $suggestion): RedirectResponse
    {
        $slug = Str::slug($suggestion->name) . '-' . Str::random(4);

        $restaurant = Restaurant::create([
            'name' => $suggestion->name,
            'slug' => $slug,
            'description' => $suggestion->description,
            'address' => $suggestion->address,
            'city' => $suggestion->city,
            'cuisine_type' => $suggestion->cuisine_type,
            'price_range' => $suggestion->price_range,
            'is_verified' => true,
            'created_by' => auth()->id(),
        ]);

        $suggestion->update([
            'status' => 'approved',
            'admin_notes' => $request->admin_notes ?? 'Approved and published to BiteClub directory.',
        ]);

        // Notify suggesting user
        Notification::create([
            'user_id' => $suggestion->user_id,
            'sender_id' => auth()->id(),
            'type' => 'suggestion_approved',
            'data' => [
                'message' => "Great news! Your restaurant suggestion for '{$suggestion->name}' has been approved and added to BiteClub.",
                'url' => route('restaurants.show', $restaurant->slug),
            ],
        ]);

        return redirect()->back()
            ->with('success', "Suggestion for '{$suggestion->name}' approved and restaurant created.");
    }

    public function reject(Request $request, RestaurantSuggestion $suggestion): RedirectResponse
    {
        $request->validate([
            'admin_notes' => ['required', 'string', 'max:500'],
        ]);

        $suggestion->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
        ]);

        // Notify suggesting user
        Notification::create([
            'user_id' => $suggestion->user_id,
            'sender_id' => auth()->id(),
            'type' => 'suggestion_rejected',
            'data' => [
                'message' => "Your restaurant suggestion for '{$suggestion->name}' was reviewed: {$request->admin_notes}",
                'url' => route('restaurants.index'),
            ],
        ]);

        return redirect()->back()
            ->with('success', "Suggestion '{$suggestion->name}' has been marked as rejected.");
    }
}
