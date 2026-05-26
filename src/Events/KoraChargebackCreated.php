<?php

declare(strict_types=1);

namespace Kora\Laravel\Events;

use Kora\Sdk\DTOs\WebhookEvent;

final class KoraChargebackCreated
{
    public function __construct(public readonly WebhookEvent $event) {}
}
