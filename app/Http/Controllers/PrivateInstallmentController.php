<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PrivateInstallmentController extends Controller
{
    public function show(Request $request): Response
    {
        $accessKey = $this->guardAccessKey($request);
        $progress = $this->progressSummary();

        return Inertia::render('PrivateInstallment', [
            'page' => [
                'title' => (string) config('private_installment.title'),
                'description' => (string) config('private_installment.description'),
                'productName' => (string) config('private_installment.product_name'),
                'packageLabel' => (string) config('private_installment.package_label'),
                'image' => (string) config('private_installment.image'),
                'targetAmount' => $progress['targetAmount'],
                'paidAmount' => $progress['paidAmount'],
                'remainingAmount' => $progress['remainingAmount'],
                'progressPercent' => $progress['progressPercent'],
                'paymentCount' => $progress['paymentCount'],
                'minimumAmount' => $this->minimumAmount($progress['remainingAmount']),
                'defaultAmount' => $this->defaultAmount($progress['remainingAmount']),
                'checkoutNotice' => (string) config('private_installment.checkout_notice'),
                'guaranteeText' => (string) config('private_installment.guarantee_text'),
                'notes' => config('private_installment.notes', []),
                'requiresAccessKey' => $this->requiresAccessKey(),
                'bankName' => (string) config('private_installment.bank_name', 'SeaBank'),
                'accountNumber' => (string) config('private_installment.account_number', ''),
                'accountHolder' => (string) config('private_installment.account_holder', ''),
            ],
            'prefill' => [
                'accessKey' => $accessKey,
            ],
        ]);
    }

    public function store(Request $request, TransactionService $transactions): RedirectResponse
    {
        $accessKey = $this->guardAccessKey($request);
        $progress = $this->progressSummary();
        $remainingAmount = $progress['remainingAmount'];

        if ($remainingAmount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Pembayaran ini sudah lunas.',
            ]);
        }

        $minimumAmount = $this->minimumAmount($remainingAmount);

        $request->validate([
            'amount' => ['required', 'integer', 'min:'.$minimumAmount, 'max:'.$remainingAmount],
            'website' => ['nullable', 'string', 'max:255'],
            'formStartedAt' => ['required', 'integer', 'min:1'],
            'accessKey' => ['nullable', 'string', 'max:120'],
        ]);

        $amount = min($remainingAmount, max($minimumAmount, (int) $request->integer('amount')));
        $guestToken = $transactions->ensureGuestToken($request);
        $now = now();

        $transaction = Transaction::create([
            'user_id' => $request->user()?->id,
            'public_id' => 'LYVA'.Str::upper(Str::random(12)),
            'guest_token' => $guestToken,
            'status' => Transaction::STATUS_PENDING,
            'payment_status' => Transaction::PAYMENT_STATUS_UNPAID,
            'product_source' => Transaction::PRODUCT_SOURCE_MANUAL,
            'product_id' => (string) config('private_installment.product_id'),
            'product_name' => (string) config('private_installment.product_name'),
            'product_image' => (string) config('private_installment.image'),
            'package_code' => null,
            'package_label' => (string) config('private_installment.package_label'),
            'quantity' => 1,
            'payment_method_code' => null,
            'payment_method_label' => (string) config('private_installment.bank_name', 'SeaBank'),
            'payment_method_type' => 'bank-transfer',
            'payment_method_image' => null,
            'payment_badge' => 'Private',
            'payment_caption' => 'Transfer manual, cek notifikasi uang masuk di email.',
            'payment_display_type' => 'bank-transfer',
            'payment_reference_label' => 'Nomor Rekening '.(string) config('private_installment.bank_name', 'SeaBank'),
            'payment_reference_value' => (string) config('private_installment.account_number', ''),
            'duitku_reference' => null,
            'duitku_payment_url' => null,
            'duitku_app_url' => null,
            'duitku_qr_string' => null,
            'subtotal' => $amount,
            'total' => $amount,
            'checkout_notice' => (string) config('private_installment.checkout_notice'),
            'guarantee_text' => (string) config('private_installment.guarantee_text'),
            'notes' => config('private_installment.notes', []),
            'summary_rows' => [
                ['label' => 'Nominal pembayaran', 'value' => $this->formatRupiah($amount)],
                ['label' => 'Total utang', 'value' => $this->formatRupiah($progress['targetAmount'])],
                ['label' => 'Sudah dibayar', 'value' => $this->formatRupiah($progress['paidAmount'])],
                ['label' => 'Sisa setelah pembayaran ini', 'value' => $this->formatRupiah(max(0, $remainingAmount - $amount))],
                ['label' => 'Bank tujuan', 'value' => (string) config('private_installment.bank_name', 'SeaBank')],
                ['label' => 'Nomor rekening', 'value' => (string) config('private_installment.account_number', '')],
                ['label' => 'Atas nama', 'value' => (string) config('private_installment.account_holder', '')],
                ['label' => 'Total pembayaran', 'value' => $this->formatRupiah($amount)],
            ],
            'account_fields' => [
                ['id' => 'private-access-key', 'label' => 'Akses private', 'value' => $accessKey],
            ],
            'contact_fields' => [],
            'customer_name' => null,
            'customer_email' => null,
            'customer_whatsapp' => null,
            'paid_at' => null,
            'expires_at' => null,
            'last_synced_at' => $now,
        ]);

        $request->session()->put('last_checkout_transaction_id', $transaction->public_id);

        return to_route('checkout.show', ['transaction' => $transaction->public_id]);
    }

    private function requiresAccessKey(): bool
    {
        return trim((string) config('private_installment.access_key', '')) !== '';
    }

    private function guardAccessKey(Request $request): string
    {
        abort_unless((bool) config('private_installment.enabled', true), 404);

        $expected = trim((string) config('private_installment.access_key', ''));
        $provided = trim((string) ($request->input('accessKey') ?: $request->query('key', '')));

        if ($expected === '') {
            return '';
        }

        abort_unless($provided !== '' && hash_equals($expected, $provided), 404);

        return $provided;
    }

    private function targetAmount(): int
    {
        return max(0, (int) config('private_installment.target_amount', 5030000));
    }

    /**
     * @return array{targetAmount:int,paidAmount:int,remainingAmount:int,progressPercent:int,paymentCount:int}
     */
    private function progressSummary(): array
    {
        $targetAmount = $this->targetAmount();
        $paidAmount = (int) Transaction::query()
            ->where('product_id', (string) config('private_installment.product_id'))
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->sum('total');
        $remainingAmount = max(0, $targetAmount - $paidAmount);
        $progressPercent = $targetAmount > 0
            ? min(100, (int) floor(($paidAmount / $targetAmount) * 100))
            : 0;
        $paymentCount = (int) Transaction::query()
            ->where('product_id', (string) config('private_installment.product_id'))
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->count();

        return [
            'targetAmount' => $targetAmount,
            'paidAmount' => $paidAmount,
            'remainingAmount' => $remainingAmount,
            'progressPercent' => $progressPercent,
            'paymentCount' => $paymentCount,
        ];
    }

    private function minimumAmount(int $remainingAmount): int
    {
        $configuredMinimum = max(50000, (int) config('private_installment.minimum_amount', 50000));

        if ($remainingAmount > 0 && $remainingAmount < $configuredMinimum) {
            return $remainingAmount;
        }

        return $configuredMinimum;
    }

    private function defaultAmount(int $remainingAmount): int
    {
        $defaultAmount = max($this->minimumAmount($remainingAmount), (int) config('private_installment.default_amount', $this->minimumAmount($remainingAmount)));

        return $remainingAmount > 0 ? min($remainingAmount, $defaultAmount) : $defaultAmount;
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp'.number_format(max(0, $amount), 0, ',', '.');
    }
}
