<?php

declare(strict_types=1);

namespace Kora\Laravel\Tests\Unit;

use Kora\Laravel\Facades\Kora;
use Kora\Laravel\Tests\TestCase;
use Kora\Sdk\Contracts\KoraClientInterface;
use Kora\Sdk\KoraClient;
use Kora\Sdk\Resources\BalancesResource;
use Kora\Sdk\Resources\BulkPayoutsResource;
use Kora\Sdk\Resources\ChargebacksResource;
use Kora\Sdk\Resources\ChargesResource;
use Kora\Sdk\Resources\ConversionsResource;
use Kora\Sdk\Resources\MobileMoneyResource;
use Kora\Sdk\Resources\PayoutsResource;
use Kora\Sdk\Resources\PoolAccountsResource;
use Kora\Sdk\Resources\RefundsResource;
use Kora\Sdk\Resources\WebhookResource;
use PHPUnit\Framework\Attributes\Test;

final class KoraFacadeTest extends TestCase
{
    #[Test]
    public function container_resolves_kora_client(): void
    {
        self::assertInstanceOf(KoraClient::class, $this->app->make(KoraClient::class));
    }

    #[Test]
    public function kora_client_interface_resolves_to_kora_client(): void
    {
        self::assertInstanceOf(KoraClient::class, $this->app->make(KoraClientInterface::class));
    }

    #[Test]
    public function facade_resolves_charges_resource(): void
    {
        self::assertInstanceOf(ChargesResource::class, Kora::charges());
    }

    #[Test]
    public function facade_resolves_mobile_money_resource(): void
    {
        self::assertInstanceOf(MobileMoneyResource::class, Kora::mobileMoney());
    }

    #[Test]
    public function facade_resolves_payouts_resource(): void
    {
        self::assertInstanceOf(PayoutsResource::class, Kora::payouts());
    }

    #[Test]
    public function facade_resolves_bulk_payouts_resource(): void
    {
        self::assertInstanceOf(BulkPayoutsResource::class, Kora::bulkPayouts());
    }

    #[Test]
    public function facade_resolves_balances_resource(): void
    {
        self::assertInstanceOf(BalancesResource::class, Kora::balances());
    }

    #[Test]
    public function facade_resolves_conversions_resource(): void
    {
        self::assertInstanceOf(ConversionsResource::class, Kora::conversions());
    }

    #[Test]
    public function facade_resolves_refunds_resource(): void
    {
        self::assertInstanceOf(RefundsResource::class, Kora::refunds());
    }

    #[Test]
    public function facade_resolves_pool_accounts_resource(): void
    {
        self::assertInstanceOf(PoolAccountsResource::class, Kora::poolAccounts());
    }

    #[Test]
    public function facade_resolves_chargebacks_resource(): void
    {
        self::assertInstanceOf(ChargebacksResource::class, Kora::chargebacks());
    }

    #[Test]
    public function facade_resolves_webhooks_resource(): void
    {
        self::assertInstanceOf(WebhookResource::class, Kora::webhooks());
    }

    #[Test]
    public function singleton_is_reused_across_resolutions(): void
    {
        self::assertSame(
            $this->app->make(KoraClient::class),
            $this->app->make(KoraClient::class),
        );
    }
}
