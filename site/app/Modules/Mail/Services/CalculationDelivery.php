<?php

declare(strict_types=1);

namespace App\Modules\Mail\Services;

use App\Modules\Mail\Mailables\CalculationMessage;
use App\Modules\Reports\Services\PdfReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

final class CalculationDelivery
{
    public function __construct(private readonly PdfReport $pdf) {}

    public function send(array $snapshot, string $recipient): bool
    {
        if (! config('mail.manobank_enabled') || ! config('mail.mailers.mailru.password')) {
            return false;
        }
        try {
            $message = new CalculationMessage($snapshot['product']['name'], $this->pdf->bytes($snapshot));
            if (Mail::mailer('mailru')->to($recipient)->send($message) === null) {
                throw new RuntimeException('Transport did not confirm acceptance');
            }

            return true;
        } catch (Throwable $exception) {
            Log::warning('mail_delivery_failed', ['product' => $snapshot['product']['code'], 'exception_type' => $exception::class]);

            return false;
        }
    }
}
