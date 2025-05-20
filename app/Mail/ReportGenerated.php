<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportGenerated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The report instance.
     *
     * @var \App\Models\Report
     */
    public $report;

    /**
     * The report download URL.
     *
     * @var string
     */
    public $downloadUrl;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\Report  $report
     * @param  string  $downloadUrl
     * @return void
     */
    public function __construct(Report $report, string $downloadUrl)
    {
        $this->report = $report;
        $this->downloadUrl = $downloadUrl;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Report is Ready: ' . $this->report->name,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reports.generated',
            with: [
                'report' => $this->report,
                'downloadUrl' => $this->downloadUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments(): array
    {
        return [];
    }
}
