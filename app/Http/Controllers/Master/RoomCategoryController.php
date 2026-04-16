<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Kost;
use App\Models\RoomCategory;
use App\Models\RoomCategoryImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RoomCategoryController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        // Keep JSON support for the modal component
        if ($request->wantsJson()) {
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

        $search = $request->input('search', '');

        $categories = RoomCategory::with(['kost', 'images', 'details', 'rooms'])
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhereHas('kost', fn ($q3) => $q3->where('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(15)
            ->through(fn (RoomCategory $cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'description' => $cat->description,
                'kost_id' => $cat->kost_id,
                'kost_name' => $cat->kost->name,
                'images_count' => $cat->images->count(),
                'details_count' => $cat->details->count(),
                'rooms_count' => $cat->rooms->count(),
            ]);

        return Inertia::render('Master/RoomCategory/Index', [
            'categories' => $categories,
            'filters' => ['search' => $search],
        ]);
    }

    public function kosts(): JsonResponse
    {
        $kosts = Kost::select('id', 'name')->get();

        return response()->json($kosts);
    }

    public function create(): Response
    {
        $kosts = Kost::select('id', 'name')->get();

        return Inertia::render('Master/RoomCategory/Form', [
            'kosts' => $kosts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kost_id' => 'required|exists:m_kosts,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
            'details' => 'nullable|array',
            'details.*.detail' => 'required|string|max:255',
            'details.*.icon' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $category = RoomCategory::create([
                'kost_id' => $validated['kost_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            // Handle images with S3Helper
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $tempFileName = \App\Helpers\S3Helper::storeFileTemp($image);
                    $s3Path = \App\Helpers\S3Helper::storeFileToS3('room-categories', $tempFileName);
                    \App\Helpers\S3Helper::removeFileTemp($tempFileName);
                    $category->images()->create(['img_url' => $s3Path]);
                }
            }

            // Handle details
            if (! empty($validated['details'])) {
                foreach ($validated['details'] as $detail) {
                    $category->details()->create([
                        'detail' => $detail['detail'],
                        'icon' => $detail['icon'] ?? null,
                    ]);
                }
            }
        });

        return to_route('master.room-categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(RoomCategory $roomCategory): Response
    {
        $roomCategory->load(['images', 'details']);
        $kosts = Kost::select('id', 'name')->get();

        return Inertia::render('Master/RoomCategory/Form', [
            'category' => [
                'id' => $roomCategory->id,
                'kost_id' => $roomCategory->kost_id,
                'name' => $roomCategory->name,
                'description' => $roomCategory->description,
                'images' => $roomCategory->images->map(fn ($img) => [
                    'id' => $img->id,
                    'img_url' => $img->img_url,
                    'full_url' => Storage::disk('public')->url($img->img_url),
                ]),
                'details' => $roomCategory->details->map(fn ($d) => [
                    'id' => $d->id,
                    'detail' => $d->detail,
                    'icon' => $d->icon,
                ]),
            ],
            'kosts' => $kosts,
        ]);
    }

    public function update(Request $request, RoomCategory $roomCategory): RedirectResponse|JsonResponse
    {
        // Keep JSON support for the modal component
        if ($request->wantsJson()) {
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

        $validated = $request->validate([
            'kost_id' => 'required|exists:m_kosts,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
            'removed_images' => 'nullable|array',
            'removed_images.*' => 'integer|exists:room_category_images,id',
            'details' => 'nullable|array',
            'details.*.id' => 'nullable|integer',
            'details.*.detail' => 'required|string|max:255',
            'details.*.icon' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($validated, $request, $roomCategory) {
            $roomCategory->update([
                'kost_id' => $validated['kost_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            // Remove deleted images
            if (! empty($validated['removed_images'])) {
                $imagesToRemove = $roomCategory->images()->whereIn('id', $validated['removed_images'])->get();
                foreach ($imagesToRemove as $img) {
                    // Hapus dari S3 jika perlu, atau cukup hapus record jika file sudah tidak ada
                    \App\Helpers\S3Helper::removeFileTemp($img->img_url); // opsional, jika ingin hapus dari temp juga
                    $img->delete();
                }
            }

            // Add new images with S3Helper
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $tempFileName = \App\Helpers\S3Helper::storeFileTemp($image);
                    $s3Path = \App\Helpers\S3Helper::storeFileToS3('room-categories', $tempFileName);
                    \App\Helpers\S3Helper::removeFileTemp($tempFileName);
                    $roomCategory->images()->create(['img_url' => $s3Path]);
                }
            }

            // Sync details
            $existingIds = [];
            if (! empty($validated['details'])) {
                foreach ($validated['details'] as $detail) {
                    if (! empty($detail['id'])) {
                        $roomCategory->details()->where('id', $detail['id'])->update([
                            'detail' => $detail['detail'],
                            'icon' => $detail['icon'] ?? null,
                        ]);
                        $existingIds[] = $detail['id'];
                    } else {
                        $newDetail = $roomCategory->details()->create([
                            'detail' => $detail['detail'],
                            'icon' => $detail['icon'] ?? null,
                        ]);
                        $existingIds[] = $newDetail->id;
                    }
                }
            }
            // Remove details not in the submitted list
            $roomCategory->details()->whereNotIn('id', $existingIds)->delete();
        });

        return to_route('master.room-categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(RoomCategory $roomCategory): RedirectResponse|JsonResponse
    {
        // Keep JSON support for the modal component
        if (request()->wantsJson()) {
            $roomCategory->delete();

            return response()->json([
                'message' => 'Kategori berhasil dihapus.',
            ]);
        }

        // Delete associated images from storage
        foreach ($roomCategory->images as $img) {
            Storage::disk('public')->delete($img->img_url);
        }

        $roomCategory->delete();

        return to_route('master.room-categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
