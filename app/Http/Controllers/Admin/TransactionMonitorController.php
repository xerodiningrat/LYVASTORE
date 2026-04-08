<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TransactionMonitorController extends Controller
{
    public function index(Request $request, TransactionService $transactions): Response
    {
        $initialFilter = (string) ($request->query('filter') ?: $request->route('filter') ?: 'all');

        $recentTransactions = Transaction::query()
            ->with('manualStockItem')
            ->latest('updated_at')
            ->latest('created_at')
            ->take(50)
            ->get();

        return Inertia::render('admin/Transactions', [
            'initialFilter' => in_array($initialFilter, ['all', 'invite-queue', 'manual-action', 'completed'], true)
                ? $initialFilter
                : 'all',
            'status' => session('status'),
            'stats' => [
                'paidToday' => (int) Transaction::query()
                    ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
                    ->whereDate('paid_at', Carbon::today())
                    ->sum('total'),
                'completedToday' => (int) Transaction::query()
                    ->where('status', Transaction::STATUS_COMPLETED)
                    ->whereDate('updated_at', Carbon::today())
                    ->count(),
                'pendingCount' => (int) Transaction::query()
                    ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING])
                    ->count(),
                'failedCount' => (int) Transaction::query()
                    ->whereIn('status', [Transaction::STATUS_FAILED, Transaction::STATUS_EXPIRED])
                    ->count(),
            ],
            'transactions' => $recentTransactions->map(function (Transaction $transaction) use ($transactions) {
                $manualAction = $transactions->manualFulfillmentActionMeta($transaction);
                $manualTargetEmail = $transactions->manualTargetEmail($transaction);
                $manualCategory = $transactions->manualFulfillmentCategory($transaction);

                return [
                    'publicId' => (string) $transaction->public_id,
                    'customerName' => (string) ($transaction->customer_name ?: 'Guest Customer'),
                    'customerWhatsapp' => (string) ($transaction->customer_whatsapp ?: '-'),
                    'customerEmail' => (string) ($transaction->customer_email ?: $manualTargetEmail ?: '-'),
                    'productName' => (string) $transaction->product_name,
                    'packageLabel' => (string) $transaction->package_label,
                    'status' => (string) $transaction->status,
                    'paymentStatus' => (string) $transaction->payment_status,
                    'paymentLabel' => (string) ($transaction->payment_method_label ?: '-'),
                    'productSource' => (string) $transaction->product_source,
                    'manualCategory' => $manualCategory,
                    'manualTargetEmail' => $manualTargetEmail,
                    'manualFulfillmentStatus' => $transaction->manual_fulfillment_status,
                    'total' => (int) $transaction->total,
                    'updatedAtLabel' => $transaction->updated_at?->locale('id')->translatedFormat('d M Y, H:i') ?? '-',
                    'checkoutUrl' => route('checkout.show', ['transaction' => $transaction->public_id]),
                    'completeManualUrl' => route('admin.transactions.manual.complete', ['transaction' => $transaction->id]),
                    'markManualPaidUrl' => route('admin.transactions.manual.mark-paid', ['transaction' => $transaction->id]),
                    'canCompleteManual' => (bool) $manualAction['canComplete'],
                    'canMarkManualPaid' => (string) $transaction->product_id === (string) config('private_installment.product_id')
                        && (string) $transaction->payment_status === Transaction::PAYMENT_STATUS_UNPAID,
                    'manualActionLabel' => (string) $manualAction['actionLabel'],
                    'manualReplyChatUrl' => $manualAction['replyChatUrl'],
                    'manualPreviewMessage' => $manualAction['previewMessage'],
                    'manualStock' => $transaction->manualStockItem ? [
                        'id' => (int) $transaction->manualStockItem->id,
                        'label' => (string) ($transaction->manualStockItem->stock_label ?: 'Data stok'),
                        'value' => (string) $transaction->manualStockItem->stock_value,
                        'notes' => $transaction->manualStockItem->notes,
                        'status' => (string) $transaction->manualStockItem->status,
                        'reservedAtLabel' => $transaction->manualStockItem->reserved_at?->locale('id')->translatedFormat('d M Y, H:i'),
                    ] : null,
                ];
            })->values()->all(),
        ]);
    }

    public function complete(Request $request, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $transactions->completeManualStockFulfillment($transaction, $request->user());

        return back()->with('status', 'Pesanan manual #'.$transaction->public_id.' berhasil diproses.');
    }

    public function markPaid(Request $request, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $transactions->markManualPaymentReceived($transaction);

        return back()->with('status', 'Pembayaran private #'.$transaction->public_id.' ditandai sudah dibayar.');
    }

    public function confirmFromTelegram(Request $request, Transaction $transaction, TransactionService $transactions): View
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $state = 'success';
        $message = 'Pesanan manual #'.$transaction->public_id.' berhasil dikonfirmasi dari Telegram.';

        if ($transaction->status === Transaction::STATUS_COMPLETED) {
            $state = 'info';
            $message = 'Pesanan manual #'.$transaction->public_id.' sebelumnya sudah selesai.';
        } else {
            try {
                $transactions->completeManualStockFulfillment($transaction, admin: null);
            } catch (\Throwable $exception) {
                report($exception);

                $state = 'error';
                $message = $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : 'Pesanan manual belum bisa dikonfirmasi dari Telegram.';
            }
        }

        return view('admin.manual-order-confirmation', [
            'state' => $state,
            'message' => $state === 'success'
                ? $message.' Status transaksi sekarang langsung berubah ke selesai, jadi kamu tidak perlu buka dashboard lagi.'
                : $message,
            'transaction' => $transaction->fresh(),
            'checkoutUrl' => route('checkout.show', ['transaction' => $transaction->public_id]),
        ]);
    }
}
