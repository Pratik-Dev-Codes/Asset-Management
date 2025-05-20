<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportGenerationFailed extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The report instance.
     *
     * @var \App\Models\Report
     */
    public $report;

    /**
     * The error message.
     *
     * @var string
     */
    public $errorMessage;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\Report  $report
     * @param  string  $errorMessage
     * @return void
     */
    public function __construct(Report $report, string $errorMessage)
    {
        $this->report = $report;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Report Generation Failed: ' . $this->report->name,
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
            view: 'emails.reports.failed',
            with: [
                'report' => $this->report,
                'errorMessage' => $this->errorMessage,
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
