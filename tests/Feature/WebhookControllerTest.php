<?php

declare(strict_types=1);

namespace Kora\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Kora\Laravel\Events\KoraChargeFailed;
use Kora\Laravel\Events\KoraChargeSucceeded;
use Kora\Laravel\Events\KoraChargebackCreated;
use Kora\Laravel\Events\KoraChargebackLost;
use Kora\Laravel\Events\KoraChargebackWon;
use Kora\Laravel\Events\KoraPayoutFailed;
use Kora\Laravel\Events\KoraPayoutSucceeded;
use Kora\Laravel\Events\KoraRefundFailed;
use Kora\Laravel\Events\KoraRefundSucceeded;
use Kora\Laravel\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class WebhookControllerTest extends TestCase
{
    private const SECRET = 'test_webhook_secret';

    /** @param array<string, mixed> $data */
    private function sign(array $data): string
    {
        return hash_hmac('sha256', json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), self::SECRET);
    }

    private function webhook(string $eventType, string $reference = 'ref_001'): \Illuminate\Testing\TestResponse
    {
        $data    = ['reference' => $reference, 'status' => 'success'];
        $payload = json_encode(['event' => $eventType, 'data' => $data], JSON_THROW_ON_ERROR);
        $sig     = $this->sign($data);

        return $this->call('POST', config('kora.webhook_path'), [], [], [], [
            'HTTP_X_KORAPAY_SIGNATURE' => $sig,
            'CONTENT_TYPE'             => 'application/json',
        ], $payload);
    }

    #[Test]
    public function charge_success_dispatches_kora_charge_succeeded(): void
    {
        Event::fake();

        $this->webhook('charge.success', 'ref_001')->assertStatus(200);

        Event::assertDispatched(KoraChargeSucceeded::class, function (KoraChargeSucceeded $e) {
            return $e->event->data['reference'] === 'ref_001';
        });
    }

    #[Test]
    public function charge_failed_dispatches_kora_charge_failed(): void
    {
        Event::fake();

        $this->webhook('charge.failed')->assertStatus(200);

        Event::assertDispatched(KoraChargeFailed::class);
    }

    #[Test]
    public function transfer_success_dispatches_kora_payout_succeeded(): void
    {
        Event::fake();

        $this->webhook('transfer.success')->assertStatus(200);

        Event::assertDispatched(KoraPayoutSucceeded::class);
    }

    #[Test]
    public function transfer_failed_dispatches_kora_payout_failed(): void
    {
        Event::fake();

        $this->webhook('transfer.failed')->assertStatus(200);

        Event::assertDispatched(KoraPayoutFailed::class);
    }

    #[Test]
    public function refund_success_dispatches_kora_refund_succeeded(): void
    {
        Event::fake();

        $this->webhook('refund.success')->assertStatus(200);

        Event::assertDispatched(KoraRefundSucceeded::class);
    }

    #[Test]
    public function refund_failed_dispatches_kora_refund_failed(): void
    {
        Event::fake();

        $this->webhook('refund.failed')->assertStatus(200);

        Event::assertDispatched(KoraRefundFailed::class);
    }

    #[Test]
    public function unknown_event_type_returns_200_without_dispatching(): void
    {
        Event::fake([
            KoraChargeSucceeded::class, KoraChargeFailed::class,
            KoraPayoutSucceeded::class, KoraPayoutFailed::class,
            KoraRefundSucceeded::class, KoraRefundFailed::class,
            KoraChargebackCreated::class, KoraChargebackWon::class, KoraChargebackLost::class,
        ]);

        $this->webhook('some.future.event')->assertStatus(200);

        Event::assertNothingDispatched();
    }

    #[Test]
    public function chargeback_created_dispatches_kora_chargeback_created(): void
    {
        Event::fake();

        $this->webhook('chargeback.created')->assertStatus(200);

        Event::assertDispatched(KoraChargebackCreated::class);
    }

    #[Test]
    public function chargeback_won_dispatches_kora_chargeback_won(): void
    {
        Event::fake();

        $this->webhook('chargeback.won')->assertStatus(200);

        Event::assertDispatched(KoraChargebackWon::class);
    }

    #[Test]
    public function chargeback_lost_dispatches_kora_chargeback_lost(): void
    {
        Event::fake();

        $this->webhook('chargeback.lost')->assertStatus(200);

        Event::assertDispatched(KoraChargebackLost::class);
    }

    #[Test]
    public function response_body_is_received_true(): void
    {
        $this->webhook('charge.success')->assertJson(['received' => true]);
    }
}
