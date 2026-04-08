<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminManualOrderAlertMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Transaction $transaction,
        public readonly ?string $stockStatusLabel = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pesanan manual baru #'.$this->transaction->public_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.manual-order-alert',
        );
    }
}
