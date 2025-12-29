<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Product $product, public int $currentStock, public int $threshold)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[ALERT] Low Stock: {$this->product->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.low-stock-notification',
            with: [
                'product' => $this->product,
                'currentStock' => $this->currentStock,
                'threshold' => $this->threshold,
            ],
        );
    }
}
