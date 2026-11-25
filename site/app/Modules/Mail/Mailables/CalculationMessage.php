<?php

declare(strict_types=1);

namespace App\Modules\Mail\Mailables;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class CalculationMessage extends Mailable
{
    public function __construct(private readonly string $productName, private readonly string $pdf) {}

    public function envelope(): Envelope
    {
        return new Envelope(from: new Address('supp.manobank@mail.ru', 'МаноБанк'), subject: 'МаноБанк — '.$this->productName);
    }

    public function content(): Content
    {
        return new Content(text: 'bankmail::calculation', with: ['productName' => $this->productName]);
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn (): string => $this->pdf, 'manobank-calculation.pdf')->withMime('application/pdf')];
    }
}
