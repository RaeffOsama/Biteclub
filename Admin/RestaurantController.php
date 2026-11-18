<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $query = Restaurant::with(['category'])->withCount(['reviews', 'posts']);

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%")
                  ->orWhere('cuisine_type', 'LIKE', "%{$search}%");
        }

        $restaurants = $query->latest()->paginate(15)->withQueryString();

        return view('admin.restaurants.index', compact('restaurants', 'search'));
    }

    public function create(): View
    {
        $categories = Category::all();
        return view('admin.restaurants.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'cuisine_type' => ['required', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price_range' => ['required', 'in:$,$$,$$$,$$$$'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'logo' => ['nullable', 'image', 'max:3072'],
            'is_verified' => ['boolean'],
        ]);

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('restaurants', 'public');
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('restaurants/logos', 'public');
        }

        $slug = Str::slug($request->name) . '-' . Str::random(4);

        Restaurant::create([
            'name' => $request->name,
            'slug' => $slug,
            'address' => $request->address,
            'city' => $request->city,
            'cuisine_type' => $request->cuisine_type,
            'category_id' => $request->category_id,
            'price_range' => $request->price_range,
            'description' => $request->description,
            'cover_image' => $coverPath,
            'logo' => $logoPath,
            'is_verified' => $request->boolean('is_verified', false),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.restaurants.index')
            ->with('success', 'Restaurant created successfully.');
    }

    public function edit(Restaurant $restaurant): View
    {
        $categories = Category::all();
        return view('admin.restaurants.edit', compact('restaurant', 'categories'));
    }

    public function update(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'cuisine_type' => ['required', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price_range' => ['required', 'in:$,$$,$$$,$$$$'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'logo' => ['nullable', 'image', 'max:3072'],
            'is_verified' => ['boolean'],
        ]);

        $data = [
            'name' => $request->name,
            'address' => $request->address,
            'city' => $request->city,
            'cuisine_type' => $request->cuisine_type,
            'category_id' => $request->category_id,
            'price_range' => $request->price_range,
            'description' => $request->description,
            'is_verified' => $request->boolean('is_verified', false),
        ];

        if ($request->hasFile('cover_image')) {
            if ($restaurant->cover_image && !str_starts_with($restaurant->cover_image, 'http')) {
                Storage::disk('public')->delete($restaurant->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('restaurants', 'public');
        }

        if ($request->hasFile('logo')) {
            if ($restaurant->logo && !str_starts_with($restaurant->logo, 'http')) {
                Storage::disk('public')->delete($restaurant->logo);
            }
            $data['logo'] = $request->file('logo')->store('restaurants/logos', 'public');
        }

        $restaurant->update($data);

        return redirect()->route('admin.restaurants.index')
            ->with('success', 'Restaurant updated successfully.');
    }

    public function destroy(Restaurant $restaurant): RedirectResponse
    {
        $restaurant->delete();

        return redirect()->route('admin.restaurants.index')
            ->with('success', 'Restaurant deleted successfully.');
    }
}
