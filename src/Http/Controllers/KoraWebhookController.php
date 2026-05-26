<?php

declare(strict_types=1);

namespace Kora\Laravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kora\Laravel\Events\KoraChargeFailed;
use Kora\Laravel\Events\KoraChargeSucceeded;
use Kora\Laravel\Events\KoraChargebackCreated;
use Kora\Laravel\Events\KoraChargebackLost;
use Kora\Laravel\Events\KoraChargebackWon;
use Kora\Laravel\Events\KoraPayoutFailed;
use Kora\Laravel\Events\KoraPayoutSucceeded;
use Kora\Laravel\Events\KoraRefundFailed;
use Kora\Laravel\Events\KoraRefundSucceeded;
use Kora\Sdk\Enums\WebhookEventType;
use Kora\Sdk\Exceptions\WebhookException;
use Kora\Sdk\Contracts\KoraClientInterface;

class KoraWebhookController extends Controller
{
    public function __construct(private readonly KoraClientInterface $kora) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            $event = $this->kora->webhooks()->parse($request->getContent());
        } catch (WebhookException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        match ($event->type) {
            WebhookEventType::ChargeSuccess => event(new KoraChargeSucceeded($event)),
            WebhookEventType::ChargeFailed  => event(new KoraChargeFailed($event)),
            WebhookEventType::PayoutSuccess => event(new KoraPayoutSucceeded($event)),
            WebhookEventType::PayoutFailed  => event(new KoraPayoutFailed($event)),
            WebhookEventType::RefundSuccess     => event(new KoraRefundSucceeded($event)),
            WebhookEventType::RefundFailed      => event(new KoraRefundFailed($event)),
            WebhookEventType::ChargebackCreated => event(new KoraChargebackCreated($event)),
            WebhookEventType::ChargebackWon     => event(new KoraChargebackWon($event)),
            WebhookEventType::ChargebackLost    => event(new KoraChargebackLost($event)),
            default                             => null,
        };

        return response()->json(['received' => true]);
    }
}
