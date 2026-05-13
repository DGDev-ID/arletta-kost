<?php

namespace App\Http\Controllers\Master;

use App\Helpers\S3Helper;
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

            $categories = $query->latest()->get()->map(fn(RoomCategory $cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'description' => $cat->description,
                'gender' => $cat->gender,
                'kost_id' => $cat->kost_id,
                'kost_name' => $cat->kost->name,
            ]);

            return response()->json($categories);
        }

        $search = $request->input('search', '');

        $categories = RoomCategory::with(['kost', 'images', 'details', 'rooms'])
            ->when($search, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                    ->orWhereHas('kost', fn($q3) => $q3->where('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(15)
            ->through(fn(RoomCategory $cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'description' => $cat->description,
                'gender' => $cat->gender,
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
            'gender' => 'nullable|in:male,female,mixed',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|max:20480',
            'details' => 'nullable|array',
            'details.*.detail' => 'required|string|max:255',
            'details.*.icon' => 'nullable|string|max:100',
            'pricings' => 'nullable|array',
            'pricings.*.duration_days' => 'required_with:pricings|integer|min:1',
            'pricings.*.price' => 'required_with:pricings|numeric|min:0',
            'pricings.*.promos' => 'nullable|array',
            'pricings.*.promos.*.type' => 'required_with:pricings.*.promos|string|max:100',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $category = RoomCategory::create([
                'kost_id' => $validated['kost_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'gender' => $validated['gender'] ?? null,
            ]);

            // Handle images with S3Helper
            if ($request->hasFile('images')) {
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $image) {
                        $tempFileName = S3Helper::storeFileTemp($image);
                        S3Helper::storeFileToS3('room_categories', $tempFileName);
                        $imgUrl = S3Helper::getUrlFileS3('room_categories', $tempFileName);
                        S3Helper::removeFileTemp($tempFileName);
                        $category->images()->create(['img_url' => $imgUrl]);
                    }
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

            // Handle pricings + promos
            if (! empty($validated['pricings'])) {
                foreach ($validated['pricings'] as $pricing) {
                    $p = $category->pricings()->create([
                        'duration_days' => $pricing['duration_days'],
                        'price' => $pricing['price'],
                    ]);

                    if (! empty($pricing['promos'])) {
                        foreach ($pricing['promos'] as $promo) {
                            $p->promos()->create([
                                'type' => $promo['type'],
                            ]);
                        }
                    }
                }
            }
        });

        return to_route('master.room-categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(RoomCategory $roomCategory): Response
    {
        $roomCategory->load(['images', 'details', 'pricings.promos']);
        $kosts = Kost::select('id', 'name')->get();

        return Inertia::render('Master/RoomCategory/Form', [
            'category' => [
                'id' => $roomCategory->id,
                'kost_id' => $roomCategory->kost_id,
                'name' => $roomCategory->name,
                'description' => $roomCategory->description,
                'gender' => $roomCategory->gender,
                'images' => $roomCategory->images->map(fn($img) => [
                    'id' => $img->id,
                    'img_url' => $img->img_url,
                    'full_url' => $img->img_url,
                    'is_cover' => (bool) $img->is_cover,
                ]),
                'details' => $roomCategory->details->map(fn($d) => [
                    'id' => $d->id,
                    'detail' => $d->detail,
                    'icon' => $d->icon,
                ]),
                'pricings' => $roomCategory->pricings->map(fn($p) => [
                    'id' => $p->id,
                    'duration_days' => $p->duration_days,
                    'price' => $p->price,
                    'promos' => $p->promos->map(fn($r) => [
                        'id' => $r->id,
                        'type' => $r->type,
                    ]),
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
                'gender' => 'nullable|in:male,female,mixed',
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
                    'gender' => $roomCategory->gender,
                    'kost_id' => $roomCategory->kost_id,
                    'kost_name' => $roomCategory->kost->name,
                ],
            ]);
        }

        $validated = $request->validate([
            'kost_id' => 'required|exists:m_kosts,id',
            'name' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female,mixed',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|max:20480',
            'removed_images' => 'nullable|array',
            'removed_images.*' => 'integer|exists:room_category_images,id',
            'details' => 'nullable|array',
            'details.*.id' => 'nullable|integer',
            'details.*.detail' => 'required|string|max:255',
            'details.*.icon' => 'nullable|string|max:100',
            'pricings' => 'nullable|array',
            'pricings.*.id' => 'nullable|integer',
            'pricings.*.duration_days' => 'required_with:pricings|integer|min:1',
            'pricings.*.price' => 'required_with:pricings|numeric|min:0',
            'pricings.*.promos' => 'nullable|array',
            'pricings.*.promos.*.id' => 'nullable|integer',
            'pricings.*.promos.*.type' => 'required_with:pricings.*.promos|string|max:100',
        ]);

        DB::transaction(function () use ($validated, $request, $roomCategory) {
            $roomCategory->update([
                'kost_id' => $validated['kost_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'gender' => $validated['gender'] ?? null,
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
                    $tempFileName = S3Helper::storeFileTemp($image);
                    S3Helper::storeFileToS3('room_categories', $tempFileName);
                    $imgUrl = S3Helper::getUrlFileS3('room_categories', $tempFileName);
                    S3Helper::removeFileTemp($tempFileName);
                    $roomCategory->images()->create(['img_url' => $imgUrl]);
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

            // Sync pricings and promos
            $existingPricingIds = [];
            if (! empty($validated['pricings'])) {
                foreach ($validated['pricings'] as $pricing) {
                    if (! empty($pricing['id'])) {
                        $roomCategory->pricings()->where('id', $pricing['id'])->update([
                            'duration_days' => $pricing['duration_days'],
                            'price' => $pricing['price'],
                        ]);
                        $pModel = $roomCategory->pricings()->where('id', $pricing['id'])->first();
                        $existingPricingIds[] = $pricing['id'];
                    } else {
                        $pModel = $roomCategory->pricings()->create([
                            'duration_days' => $pricing['duration_days'],
                            'price' => $pricing['price'],
                        ]);
                        $existingPricingIds[] = $pModel->id;
                    }

                    // Sync promos for this pricing
                    $existingPromoIds = [];
                    if (! empty($pricing['promos'])) {
                        foreach ($pricing['promos'] as $promo) {
                            if (! empty($promo['id'])) {
                                $pModel->promos()->where('id', $promo['id'])->update([
                                    'type' => $promo['type'],
                                ]);
                                $existingPromoIds[] = $promo['id'];
                            } else {
                                $newPromo = $pModel->promos()->create([
                                    'type' => $promo['type'],
                                ]);
                                $existingPromoIds[] = $newPromo->id;
                            }
                        }
                    }
                    // remove promos not in submitted list
                    $pModel->promos()->whereNotIn('id', $existingPromoIds)->delete();
                }
            }
            // Remove pricings not in submitted list
            $roomCategory->pricings()->whereNotIn('id', $existingPricingIds)->delete();
        });

        return to_route('master.room-categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function setCoverImage(RoomCategory $roomCategory, RoomCategoryImage $image): JsonResponse
    {
        if ($image->room_category_id !== $roomCategory->id) {
            return response()->json(['message' => 'Gambar tidak ditemukan pada kategori ini.'], 404);
        }

        // Unset all covers for this category, then set the chosen one
        $roomCategory->images()->update(['is_cover' => false]);
        $image->update(['is_cover' => true]);

        return response()->json(['message' => 'Cover gambar berhasil diatur.']);
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
