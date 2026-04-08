<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PromoCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PromoCodeController extends Controller
{
    public function index(PromoCodeService $promos): Response
    {
        return Inertia::render('admin/Promos', [
            'status' => session('status'),
            'promos' => $promos->all(),
        ]);
    }

    public function store(Request $request, PromoCodeService $promos): RedirectResponse
    {
        $promos->save(null, $this->validatedPromo($request));

        return back()->with('status', 'Kode promo berhasil dibuat.');
    }

    public function update(Request $request, string $promo, PromoCodeService $promos): RedirectResponse
    {
        $promos->save($promo, $this->validatedPromo($request));

        return back()->with('status', 'Kode promo berhasil diperbarui.');
    }

    public function destroy(string $promo, PromoCodeService $promos): RedirectResponse
    {
        $promos->delete($promo);

        return back()->with('status', 'Kode promo berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPromo(Request $request): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'label' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:180'],
            'type' => ['required', Rule::in(['fixed', 'percent'])],
            'value' => ['required', 'numeric', 'min:1'],
            'minimum_subtotal' => ['nullable', 'integer', 'min:0'],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'product_ids' => ['nullable', 'string', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (($validated['type'] ?? null) === 'percent' && (float) ($validated['value'] ?? 0) > 100) {
            throw ValidationException::withMessages([
                'value' => 'Promo persen maksimal 100.',
            ]);
        }

        return $validated;
    }
}
