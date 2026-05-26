# kora-laravel

Laravel integration for the [Kora PHP SDK](https://github.com/mosesadewale/kora-php). Provides a service provider, facade, and a full webhook pipeline with typed Laravel events.

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12
- [mosesadewale/kora-php](https://github.com/mosesadewale/kora-php) ^1.0

## Installation

```bash
composer require mosesadewale/kora-laravel
```

The service provider and `Kora` facade are auto-discovered via Laravel's package discovery.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=kora-config
```

Then add to your `.env`:

```env
KORA_SECRET_KEY=sk_live_...
KORA_ENCRYPTION_KEY=...        # required for card payments (32 bytes)
KORA_WEBHOOK_SECRET=wh_...
KORA_ENVIRONMENT=live          # live or sandbox
```

Full config reference (`config/kora.php`):

```php
return [
    'secret_key'             => env('KORA_SECRET_KEY', ''),
    'encryption_key'         => env('KORA_ENCRYPTION_KEY', ''),
    'environment'            => env('KORA_ENVIRONMENT', 'live'),
    'webhook_secret'         => env('KORA_WEBHOOK_SECRET', ''),
    'webhook_path'           => env('KORA_WEBHOOK_PATH', 'webhooks/kora'),
    'register_webhook_route' => env('KORA_REGISTER_ROUTE', true),
    'timeout'                => (float) env('KORA_TIMEOUT', 30),
    'retry_attempts'         => (int)   env('KORA_RETRY_ATTEMPTS', 3),
];
```

> `sk_live_` keys must be used with `KORA_ENVIRONMENT=live`; `sk_test_` keys with `KORA_ENVIRONMENT=sandbox`. A mismatch throws `InvalidArgumentException` at boot time.

## Usage

Use the `Kora` facade anywhere in your application:

```php
use Kora\Laravel\Facades\Kora;

// Initialize a charge
$charge = Kora::charges()->charge([
    'reference'    => 'ref_' . uniqid(),
    'amount'       => 5000,
    'currency'     => 'NGN',
    'customer'     => ['email' => 'user@example.com', 'name' => 'Ada Okonkwo'],
    'redirect_url' => 'https://yourapp.com/callback',
]);

return redirect($charge->checkoutUrl);
```

All resources are available on the facade:

```php
Kora::charges()        // ChargesResource
Kora::mobileMoney()    // MobileMoneyResource
Kora::payouts()        // PayoutsResource
Kora::bulkPayouts()    // BulkPayoutsResource
Kora::balances()       // BalancesResource
Kora::conversions()    // ConversionsResource
Kora::refunds()        // RefundsResource
Kora::poolAccounts()   // PoolAccountsResource
Kora::chargebacks()    // ChargebacksResource
Kora::webhooks()       // WebhookResource
```

See the [kora-php README](https://github.com/mosesadewale/kora-php) for full method signatures and usage examples for each resource.

## Webhooks

### Automatic route

By default the package registers `POST webhooks/kora` with signature verification middleware applied. The route is registered outside Laravel's `web` middleware group — no CSRF exemption is needed.

> If you set `KORA_REGISTER_ROUTE=false` and add the route yourself in `routes/web.php`, exclude it from CSRF protection in your app's `VerifyCsrfToken` middleware (Laravel 10) or `bootstrap/app.php` (Laravel 11+).

### Laravel events

When a valid webhook arrives the controller dispatches a typed event. Listen for them anywhere in your application:

```php
use Kora\Laravel\Events\KoraChargeSucceeded;
use Kora\Laravel\Events\KoraPayoutSucceeded;
use Kora\Laravel\Events\KoraRefundSucceeded;

// AppServiceProvider::boot() or EventServiceProvider
Event::listen(KoraChargeSucceeded::class, function (KoraChargeSucceeded $e) {
    Order::where('payment_reference', $e->event->data['reference'])
         ->update(['status' => 'paid']);
});

Event::listen(KoraPayoutSucceeded::class, function (KoraPayoutSucceeded $e) {
    Payout::where('reference', $e->event->data['reference'])
           ->update(['status' => 'completed']);
});

Event::listen(KoraRefundSucceeded::class, function (KoraRefundSucceeded $e) {
    Order::where('refund_reference', $e->event->data['reference'])
          ->update(['status' => 'refunded']);
});
```

**All dispatched events:**

| Event class | Kora event |
|---|---|
| `KoraChargeSucceeded` | `charge.success` |
| `KoraChargeFailed` | `charge.failed` |
| `KoraPayoutSucceeded` | `transfer.success` |
| `KoraPayoutFailed` | `transfer.failed` |
| `KoraRefundSucceeded` | `refund.success` |
| `KoraRefundFailed` | `refund.failed` |
| `KoraChargebackCreated` | `chargeback.created` |
| `KoraChargebackWon` | `chargeback.won` |
| `KoraChargebackLost` | `chargeback.lost` |

Each event carries a `WebhookEvent $event` property:

```php
$e->event->type;             // WebhookEventType enum case (or null for unknown types)
$e->event->event;            // raw event string e.g. "charge.success"
$e->event->data;             // array — the full data payload from Kora
$e->event->data['reference'] // the transaction reference
```

### Custom webhook route

If you need full control, disable the built-in route and define your own:

```php
// KORA_REGISTER_ROUTE=false in .env

// routes/api.php
Route::post('webhooks/kora', function (Request $request) {
    $raw       = $request->getContent();
    $signature = $request->headers->get('x-korapay-signature') ?? '';

    if (!Kora::webhooks()->verify($raw, $signature)) {
        abort(401);
    }

    $event = Kora::webhooks()->parse($raw);
    // handle $event manually
    return response()->json(['received' => true]);
});
```

## Error handling

```php
use Kora\Sdk\Exceptions\ApiException;
use Kora\Sdk\Exceptions\AuthenticationException;
use Kora\Sdk\Exceptions\DuplicateReferenceException;
use Kora\Sdk\Exceptions\InsufficientFundsException;
use Kora\Sdk\Exceptions\KoraException;
use Kora\Sdk\Exceptions\NetworkException;
use Kora\Sdk\Exceptions\ValidationException;

try {
    Kora::payouts()->disburse($payload);
} catch (DuplicateReferenceException $e) {
    // reference already used
} catch (InsufficientFundsException $e) {
    // wallet balance too low
} catch (ValidationException $e) {
    Log::warning('Kora validation', $e->errors());
} catch (ApiException $e) {
    Log::error('Kora server error', ['context' => $e->context()]);
} catch (KoraException $e) {
    Log::error($e->getMessage());
}
```

## Testing

Swap the HTTP client for `FakeHttpClient` in tests — no network calls, full assertion surface:

```php
use Kora\Laravel\Facades\Kora;
use Kora\Sdk\Enums\Environment;
use Kora\Sdk\Factory;
use Kora\Sdk\Support\KoraConfig;
use Kora\Sdk\Tests\Fakes\FakeHttpClient;

// In a test or ServiceProvider override
$http = new FakeHttpClient(['data' => ['reference' => 'ref_001', 'status' => 'success', 'checkout_url' => 'https://pay.korapay.com/xxx']]);

$this->app->instance(
    \Kora\Sdk\Contracts\KoraClientInterface::class,
    Factory::withClient($http, new KoraConfig(secretKey: 'sk_test_key', environment: Environment::Sandbox)),
);

$response = Kora::charges()->charge([...]);
self::assertSame('ref_001', $response->reference);
```

For webhook controller tests, use `Event::fake()` and post a signed payload:

```php
use Illuminate\Support\Facades\Event;
use Kora\Laravel\Events\KoraChargeSucceeded;

Event::fake();

$secret  = config('kora.webhook_secret');
$data    = ['reference' => 'ref_001', 'status' => 'success'];
$payload = json_encode(['event' => 'charge.success', 'data' => $data]);
$sig     = hash_hmac('sha256', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $secret);

$this->call('POST', config('kora.webhook_path'), [], [], [], [
    'HTTP_X_KORAPAY_SIGNATURE' => $sig,
    'CONTENT_TYPE'             => 'application/json',
], $payload)->assertStatus(200);

Event::assertDispatched(KoraChargeSucceeded::class, function (KoraChargeSucceeded $e) {
    return $e->event->data['reference'] === 'ref_001';
});
```

## Laravel

This package is the Laravel integration. For framework-agnostic usage see [mosesadewale/kora-php](https://github.com/mosesadewale/kora-php).

## License

MIT
