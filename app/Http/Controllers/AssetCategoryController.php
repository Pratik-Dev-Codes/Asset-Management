<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class AssetCategoryController extends Controller
{
    /**
     * Display the asset categories landing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function landing()
    {
        return view('asset_categories.landing');
    }

    /**
     * Display a listing of the asset categories.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Check permission
        if (! auth()->user()->can('asset.category.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view asset categories.');
        }

        $categories = AssetCategory::withCount('assets')->get();

        return view('asset-categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new asset category.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Check permission
        if (! auth()->user()->can('asset.category.create')) {
            return redirect()->route('asset-categories.index')->with('error', 'You do not have permission to create asset categories.');
        }

        return view('asset-categories.create');
    }

    /**
     * Store a newly created asset category in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check permission
        if (! auth()->user()->can('asset.category.create')) {
            return redirect()->route('asset-categories.index')->with('error', 'You do not have permission to create asset categories.');
        }

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:asset_categories',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Create the category
        $category = new AssetCategory;
        $category->name = $request->name;
        $category->description = $request->description;

        // Handle icon upload
        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('asset-categories/icons', 'public');
            $category->icon = $path;

            // Resize icon if needed
            $img = Image::make(public_path('storage/'.$path))
                ->resize(64, 64, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            $img->save();
        }

        $category->save();

        return redirect()->route('asset-categories.index')
            ->with('success', 'Asset category created successfully.');
    }

    /**
     * Display the specified asset category.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(AssetCategory $category)
    {
        // Check permission
        if (! auth()->user()->can('asset.category.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view asset categories.');
        }

        $category->load(['assets' => function ($query) {
            $query->paginate(10);
        }]);

        return view('asset-categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified asset category.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(AssetCategory $category)
    {
        // Check permission
        if (! auth()->user()->can('asset.category.edit')) {
            return redirect()->route('asset-categories.show', $category)->with('error', 'You do not have permission to edit asset categories.');
        }

        return view('asset-categories.edit', compact('category'));
    }

    /**
     * Update the specified asset category in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AssetCategory $category)
    {
        // Check permission
        if (! auth()->user()->can('asset.category.edit')) {
            return redirect()->route('asset-categories.show', $category)->with('error', 'You do not have permission to edit asset categories.');
        }

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:asset_categories,name,'.$category->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Update the category
        $category->name = $request->name;
        $category->description = $request->description;

        // Handle icon upload
        if ($request->hasFile('icon')) {
            // Delete old icon if exists
            if ($category->icon) {
                Storage::disk('public')->delete($category->icon);
            }

            $path = $request->file('icon')->store('asset-categories/icons', 'public');
            $category->icon = $path;

            // Resize icon if needed
            $img = Image::make(public_path('storage/'.$path))
                ->resize(64, 64, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            $img->save();
        }

        $category->save();

        return redirect()->route('asset-categories.index')
            ->with('success', 'Asset category updated successfully.');
    }

    /**
     * Remove the specified asset category from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(AssetCategory $category)
    {
        // Check permission
        if (! auth()->user()->can('asset.category.delete')) {
            return redirect()->route('asset-categories.index')->with('error', 'You do not have permission to delete asset categories.');
        }

        // Check if category has assets
        if ($category->assets()->count() > 0) {
            return redirect()->route('asset-categories.index')
                ->with('error', 'Cannot delete category that has assets assigned to it.');
        }

        // Delete icon if exists
        if ($category->icon) {
            Storage::disk('public')->delete($category->icon);
        }

        // Delete the category
        $category->delete();

        return redirect()->route('asset-categories.index')
            ->with('success', 'Asset category deleted successfully.');
    }

    /**
     * Get all categories as JSON for API or AJAX requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategories()
    {
        $categories = AssetCategory::select('id', 'name')->get();

        return response()->json($categories);
    }
}
