<?php

namespace App\Mail;

use App\Models\DailySalesReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySalesReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DailySalesReport $report)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Daily Sales Report - {$this->report->report_date->format('F j, Y')}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.daily-sales-report',
            with: [
                'report' => $this->report,
            ],
        );
    }
}
