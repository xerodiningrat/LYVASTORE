<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualStockItem;
use App\Models\Transaction;
use App\Services\ManualStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ManualStockController extends Controller
{
    public function index(ManualStockService $manualStock): Response
    {
        return Inertia::render('admin/ManualStock', [
            'status' => session('status'),
            'productOptions' => $manualStock->managedProductOptions(),
            'stats' => [
                'availableCount' => (int) ManualStockItem::query()->where('status', ManualStockItem::STATUS_AVAILABLE)->count(),
                'reservedCount' => (int) ManualStockItem::query()->where('status', ManualStockItem::STATUS_RESERVED)->count(),
                'usedCount' => (int) ManualStockItem::query()->where('status', ManualStockItem::STATUS_USED)->count(),
                'waitingOrders' => (int) Transaction::query()
                    ->where('product_source', Transaction::PRODUCT_SOURCE_MANUAL_STOCK)
                    ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
                    ->where('status', Transaction::STATUS_PROCESSING)
                    ->where('manual_fulfillment_status', Transaction::MANUAL_FULFILLMENT_WAITING_STOCK)
                    ->count(),
            ],
            'items' => $manualStock->adminStockItemsPayload(),
        ]);
    }

    public function store(Request $request, ManualStockService $manualStock): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'string', 'max:255'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'package_label' => ['required', 'string', 'max:255'],
            'stock_label' => ['nullable', 'string', 'max:255'],
            'stock_values' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $storedItems = $manualStock->storeFromTextarea($validated, $request->user());

        return back()->with('status', $storedItems->count().' stok manual berhasil ditambahkan.');
    }
}
