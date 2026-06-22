<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::forUser($request->user()->id)
            ->ordered()
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);

        $customCount = Category::forUser($request->user()->id)->custom()->count();

        if ($customCount >= 20) {
            return response()->json([
                'message' => 'Anda telah mencapai batas maksimal 20 kategori custom.',
                'errors' => ['name' => ['Batas maksimal 20 kategori custom tercapai.']],
            ], 422);
        }

        $category = $request->user()->categories()->create([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? '📌',
            'is_default' => false,
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if ($category->is_default) {
            return response()->json(['message' => 'Kategori default tidak dapat diubah.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:10'],
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if ($category->is_default) {
            return response()->json(['message' => 'Kategori default tidak dapat dihapus.'], 403);
        }

        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }

    public function toggleFavorite(Request $request, Category $category): JsonResponse
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        $category->update(['is_favorite' => ! $category->is_favorite]);

        return response()->json($category);
    }
}
