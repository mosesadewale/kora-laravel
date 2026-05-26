<?php

declare(strict_types=1);

namespace Kora\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Kora\Sdk\Contracts\KoraClientInterface;
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

/**
 * @method static ChargesResource      charges()
 * @method static MobileMoneyResource  mobileMoney()
 * @method static PayoutsResource      payouts()
 * @method static BulkPayoutsResource  bulkPayouts()
 * @method static BalancesResource     balances()
 * @method static ConversionsResource  conversions()
 * @method static RefundsResource      refunds()
 * @method static PoolAccountsResource poolAccounts()
 * @method static ChargebacksResource  chargebacks()
 * @method static WebhookResource      webhooks()
 *
 * @see \Kora\Sdk\KoraClient
 */
class Kora extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return KoraClientInterface::class;
    }
}
