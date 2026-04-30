<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\TermsCondition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TermsConditionController extends Controller
{
    /**
     * Store a new term.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $maxOrder = TermsCondition::max('order') ?? 0;

        TermsCondition::create([
            'order'   => $maxOrder + 1,
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Syarat & ketentuan berhasil ditambahkan.');
    }

    /**
     * Bulk update all terms (order + content).
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'terms'              => 'required|array',
            'terms.*.id'         => 'nullable|exists:terms_conditions,id',
            'terms.*.content'    => 'required|string|max:1000',
            'terms.*.order'      => 'required|integer|min:1',
        ]);

        // Delete all existing terms and re-insert (simplest approach for reorder + edit)
        TermsCondition::query()->delete();

        foreach ($validated['terms'] as $term) {
            TermsCondition::create([
                'order'   => $term['order'],
                'content' => $term['content'],
            ]);
        }

        return back()->with('success', 'Syarat & ketentuan berhasil diperbarui.');
    }

    /**
     * Delete a single term.
     */
    public function destroy(TermsCondition $term): RedirectResponse
    {
        $term->delete();

        // Re-order remaining terms
        TermsCondition::orderBy('order')
            ->get()
            ->each(function ($t, $index) {
                $t->update(['order' => $index + 1]);
            });

        return back()->with('success', 'Syarat & ketentuan berhasil dihapus.');
    }
}
