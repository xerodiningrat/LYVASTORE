<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleSheetsSyncService
{
    public function configured(): bool
    {
        return filled(config('services.google_sheets.webhook_url'));
    }

    public function syncTransaction(Transaction $transaction, string $event = 'updated'): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $response = Http::timeout((int) config('services.google_sheets.timeout', 15))
            ->acceptJson()
            ->withHeaders($this->headers())
            ->post((string) config('services.google_sheets.webhook_url'), [
                'event' => $event,
                'source' => 'lyvaindonesia',
                'sentAt' => now()->toIso8601String(),
                'transaction' => $this->payload($transaction),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Google Sheets sync failed with status '.$response->status().'.');
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $headers = [];
        $token = trim((string) config('services.google_sheets.webhook_token'));

        if ($token !== '') {
            $headers['X-LYVA-SHEETS-TOKEN'] = $token;
        }

        return $headers;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Transaction $transaction): array
    {
        return [
            'id' => (int) $transaction->id,
            'publicId' => (string) $transaction->public_id,
            'status' => (string) $transaction->status,
            'paymentStatus' => (string) $transaction->payment_status,
            'productSource' => (string) ($transaction->product_source ?? ''),
            'productId' => (string) ($transaction->product_id ?? ''),
            'productName' => (string) ($transaction->product_name ?? ''),
            'packageCode' => $transaction->package_code,
            'packageLabel' => (string) ($transaction->package_label ?? ''),
            'quantity' => (int) ($transaction->quantity ?? 1),
            'subtotal' => (int) ($transaction->subtotal ?? 0),
            'promoCode' => $transaction->promo_code,
            'promoLabel' => $transaction->promo_label,
            'promoDiscount' => (int) ($transaction->promo_discount ?? 0),
            'total' => (int) ($transaction->total ?? 0),
            'paymentMethodCode' => $transaction->payment_method_code,
            'paymentMethodLabel' => $transaction->payment_method_label,
            'customerName' => $transaction->customer_name,
            'customerEmail' => $transaction->customer_email,
            'customerWhatsapp' => $transaction->customer_whatsapp,
            'errorMessage' => $transaction->error_message,
            'fulfillmentNote' => $transaction->fulfillment_note,
            'ratingScore' => $transaction->rating_score ? (int) $transaction->rating_score : null,
            'ratingComment' => $transaction->rating_comment,
            'accountSummary' => $this->flattenFields($transaction->account_fields ?? []),
            'contactSummary' => $this->flattenFields($transaction->contact_fields ?? []),
            'createdAt' => $transaction->created_at?->toIso8601String(),
            'updatedAt' => $transaction->updated_at?->toIso8601String(),
            'paidAt' => $transaction->paid_at?->toIso8601String(),
            'fulfilledAt' => $transaction->fulfilled_at?->toIso8601String(),
            'ratedAt' => $transaction->rated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<int, array{id?: string, label?: string, value?: string}>  $fields
     */
    private function flattenFields(array $fields): string
    {
        return collect($fields)
            ->map(function (array $field): ?string {
                $label = trim((string) ($field['label'] ?? $field['id'] ?? ''));
                $value = trim((string) ($field['value'] ?? ''));

                if ($label === '' || $value === '') {
                    return null;
                }

                return $label.': '.$value;
            })
            ->filter()
            ->implode(' | ');
    }
}
