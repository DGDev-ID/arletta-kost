<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Kost;
use App\Models\RoomCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RoomCategory::with('kost');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->latest()->get()->map(fn (RoomCategory $cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'description' => $cat->description,
            'kost_id' => $cat->kost_id,
            'kost_name' => $cat->kost->name,
        ]);

        return response()->json($categories);
    }

    public function kosts(): JsonResponse
    {
        $kosts = Kost::select('id', 'name')->get();

        return response()->json($kosts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kost_id' => 'required|exists:m_kosts,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = RoomCategory::create($validated);
        $category->load('kost');

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'kost_id' => $category->kost_id,
                'kost_name' => $category->kost->name,
            ],
        ], 201);
    }

    public function update(Request $request, RoomCategory $roomCategory): JsonResponse
    {
        $validated = $request->validate([
            'kost_id' => 'required|exists:m_kosts,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $roomCategory->update($validated);
        $roomCategory->load('kost');

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'category' => [
                'id' => $roomCategory->id,
                'name' => $roomCategory->name,
                'description' => $roomCategory->description,
                'kost_id' => $roomCategory->kost_id,
                'kost_name' => $roomCategory->kost->name,
            ],
        ]);
    }

    public function destroy(RoomCategory $roomCategory): JsonResponse
    {
        $roomCategory->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }
}
