<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingGallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LandingGalleryController extends Controller
{
    /**
     * Display a listing of the galleries.
     */
    public function index(Request $request)
    {
        $query = LandingGallery::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $galleries = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->paginate(12)->withQueryString();
        $categories = LandingGallery::categories();

        return view('admin.landing-gallery.index', compact('galleries', 'categories'));
    }

    /**
     * Store a newly created gallery item in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'category'    => 'required|string|in:production,facility,quality_control,warehouse,office',
            'image'       => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $imagePath = $request->file('image')->store('landing_galleries', 'public');

        LandingGallery::create([
            'title'       => $request->title,
            'category'    => $request->category,
            'image_path'  => $imagePath,
            'description' => $request->description,
            'sort_order'  => (int) ($request->sort_order ?? 0),
            'is_active'   => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.landing-gallery.index')->with('success', 'Foto galeri berhasil ditambahkan.');
    }

    /**
     * Update the specified gallery item in storage.
     */
    public function update(Request $request, LandingGallery $landingGallery)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'category'    => 'required|string|in:production,facility,quality_control,warehouse,office',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $data = [
            'title'       => $request->title,
            'category'    => $request->category,
            'description' => $request->description,
            'sort_order'  => (int) ($request->sort_order ?? 0),
            'is_active'   => $request->boolean('is_active'),
        ];

        if ($request->hasFile('image')) {
            // Delete old file if stored on disk
            if ($landingGallery->image_path && !str_starts_with($landingGallery->image_path, 'http')) {
                Storage::disk('public')->delete($landingGallery->image_path);
            }
            $data['image_path'] = $request->file('image')->store('landing_galleries', 'public');
        }

        $landingGallery->update($data);

        return redirect()->route('admin.landing-gallery.index')->with('success', 'Foto galeri berhasil diperbarui.');
    }

    /**
     * Toggle active status.
     */
    public function toggle(LandingGallery $landingGallery)
    {
        $landingGallery->update([
            'is_active' => !$landingGallery->is_active,
        ]);

        return back()->with('success', 'Status galeri diperbarui.');
    }

    /**
     * Remove the specified gallery item from storage.
     */
    public function destroy(LandingGallery $landingGallery)
    {
        if ($landingGallery->image_path && !str_starts_with($landingGallery->image_path, 'http')) {
            Storage::disk('public')->delete($landingGallery->image_path);
        }

        $landingGallery->delete();

        return redirect()->route('admin.landing-gallery.index')->with('success', 'Foto galeri berhasil dihapus.');
    }
}