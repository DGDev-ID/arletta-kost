<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PromoController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');

        $promos = Promo::when($search, fn ($q) => $q->where(function ($q2) use ($search) {
            $q2->where('name', 'like', "%{$search}%")
               ->orWhere('code', 'like', "%{$search}%");
        }))
            ->latest()
            ->paginate(15)
            ->through(fn (Promo $p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'code'        => $p->code,
                'description' => $p->description,
                'type'        => $p->type,
                'value'       => (float) $p->value,
                'min_purchase'=> $p->min_purchase !== null ? (float) $p->min_purchase : null,
                'max_usage'   => $p->max_usage,
                'usage_count' => $p->usage_count,
                'is_active'   => $p->is_active,
                'valid_from'  => $p->valid_from?->toDateString(),
                'valid_until' => $p->valid_until?->toDateString(),
            ]);

        return Inertia::render('Master/Promo/Index', [
            'promos'  => $promos,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Master/Promo/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'code'         => 'required|string|max:50|unique:promos,code',
            'description'  => 'nullable|string',
            'type'         => 'required|in:discount_percent,discount_amount,bonus_days',
            'value'        => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_usage'    => 'nullable|integer|min:1',
            'is_active'    => 'boolean',
            'valid_from'   => 'nullable|date',
            'valid_until'  => 'nullable|date|after_or_equal:valid_from',
        ]);

        // Normalise code: uppercase
        $validated['code'] = Str::upper($validated['code']);

        if ($validated['type'] === 'discount_percent' && $validated['value'] > 100) {
            return back()->withErrors(['value' => 'Persentase diskon tidak boleh lebih dari 100.'])->withInput();
        }

        Promo::create($validated);

        return redirect()->route('master.promos.index')
            ->with('success', 'Promo berhasil ditambahkan.');
    }

    public function edit(Promo $promo): Response
    {
        return Inertia::render('Master/Promo/Form', [
            'promo' => [
                'id'          => $promo->id,
                'name'        => $promo->name,
                'code'        => $promo->code,
                'description' => $promo->description,
                'type'        => $promo->type,
                'value'       => (float) $promo->value,
                'min_purchase'=> $promo->min_purchase !== null ? (float) $promo->min_purchase : null,
                'max_usage'   => $promo->max_usage,
                'is_active'   => $promo->is_active,
                'valid_from'  => $promo->valid_from?->toDateString(),
                'valid_until' => $promo->valid_until?->toDateString(),
            ],
        ]);
    }

    public function update(Request $request, Promo $promo): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'code'         => ['required', 'string', 'max:50', Rule::unique('promos', 'code')->ignore($promo->id)],
            'description'  => 'nullable|string',
            'type'         => 'required|in:discount_percent,discount_amount,bonus_days',
            'value'        => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'max_usage'    => 'nullable|integer|min:1',
            'is_active'    => 'boolean',
            'valid_from'   => 'nullable|date',
            'valid_until'  => 'nullable|date|after_or_equal:valid_from',
        ]);

        $validated['code'] = Str::upper($validated['code']);

        if ($validated['type'] === 'discount_percent' && $validated['value'] > 100) {
            return back()->withErrors(['value' => 'Persentase diskon tidak boleh lebih dari 100.'])->withInput();
        }

        $promo->update($validated);

        return redirect()->route('master.promos.index')
            ->with('success', 'Promo berhasil diperbarui.');
    }

    public function destroy(Promo $promo): RedirectResponse
    {
        $promo->delete();

        return back()->with('success', 'Promo berhasil dihapus.');
    }
}
