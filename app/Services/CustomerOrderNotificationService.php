<?php

namespace App\Services;

use App\Mail\OrderCompletedMail;
use App\Models\Transaction;
use Illuminate\Support\Facades\Mail;

class CustomerOrderNotificationService
{
    public function sendCompletedOrder(Transaction $transaction): Transaction
    {
        if ($transaction->customer_completed_emailed_at || ! filled($transaction->customer_email)) {
            return $transaction;
        }

        Mail::to((string) $transaction->customer_email)->send(new OrderCompletedMail($transaction));

        $transaction->forceFill([
            'customer_completed_emailed_at' => now(),
        ])->save();

        return $transaction->fresh();
    }
}
