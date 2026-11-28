<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Mail;

use App\Modules\Catalog\Database\Seeders\CalculatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

final class CalculationMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CalculatorSeeder::class);
    }

    public function test_disabled_smtp_cannot_report_success_or_log_a_fake_letter(): void
    {
        $id = $this->calculation();
        config(['mail.manobank_enabled' => false]);
        $this->postJson('/calculations/'.$id.'/email', ['email' => 'recipient@example.com'])->assertStatus(503)
            ->assertJsonMissing(['success' => true]);
    }

    public function test_component_transport_receives_exactly_one_pdf_without_an_admin_copy(): void
    {
        $id = $this->calculation();
        config(['mail.manobank_enabled' => true, 'mail.mailers.mailru.password' => 'local-test-fixture']);
        $transport = new ArrayTransport;
        Mail::mailer('mailru')->setSymfonyTransport($transport);
        $this->postJson('/calculations/'.$id.'/email', ['email' => 'recipient@example.com'])->assertOk()->assertJsonPath('success', true);
        self::assertCount(1, $transport->messages());
        $message = $transport->messages()[0]->getOriginalMessage();
        self::assertSame('recipient@example.com', $message->getTo()[0]->getAddress());
        self::assertSame('supp.manobank@mail.ru', $message->getFrom()[0]->getAddress());
        self::assertSame([], $message->getCc());
        self::assertSame([], $message->getBcc());
        self::assertCount(1, $message->getAttachments());
        self::assertSame('application/pdf', $message->getAttachments()[0]->getMediaType().'/'.$message->getAttachments()[0]->getMediaSubtype());
        self::assertStringStartsWith('%PDF-', $message->getAttachments()[0]->getBody());
    }

    public function test_invalid_recipient_and_another_session_cannot_send_a_calculation(): void
    {
        $id = $this->calculation();
        $this->postJson('/calculations/'.$id.'/email', ['email' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->withCookie(config('session.cookie'), Str::random(40));
        $this->postJson('/calculations/'.$id.'/email', ['email' => 'recipient@example.com'])->assertNotFound();
    }

    public function test_calculations_do_not_consume_the_email_request_limit(): void
    {
        config(['mail.manobank_enabled' => true, 'mail.mailers.mailru.password' => 'local-test-fixture']);
        Mail::mailer('mailru')->setSymfonyTransport(new ArrayTransport);

        for ($number = 0; $number < 5; $number++) {
            $id = $this->calculation();
            $this->postJson('/calculations/'.$id.'/email', ['email' => 'recipient@example.com'])
                ->assertOk()->assertJsonPath('success', true);
        }

        $this->postJson('/calculations/'.$id.'/email', ['email' => 'recipient@example.com'])->assertStatus(429);
        $this->postJson('/calculations/consumer', ['amount' => 100000, 'years' => 1])->assertCreated();
    }

    public function test_transport_failure_returns_an_error_without_exposing_provider_details(): void
    {
        $id = $this->calculation();
        config(['mail.manobank_enabled' => true, 'mail.mailers.mailru.password' => 'local-test-fixture']);
        $transport = new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new \RuntimeException('private-transport-details');
            }

            public function __toString(): string
            {
                return 'component-failure';
            }
        };
        Mail::mailer('mailru')->setSymfonyTransport($transport);
        $this->postJson('/calculations/'.$id.'/email', ['email' => 'recipient@example.com'])->assertStatus(503)
            ->assertDontSee('private-transport-details');
    }

    private function calculation(): string
    {
        $id = $this->postJson('/calculations/consumer', ['amount' => 100000, 'years' => 1])->assertCreated()->json('id');
        $this->withCookie(config('session.cookie'), $this->app['session']->getId());
        $this->withCredentials();

        return $id;
    }
}
