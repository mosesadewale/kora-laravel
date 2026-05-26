<?php

declare(strict_types=1);

namespace Kora\Laravel\Tests\Feature;

use Kora\Laravel\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class WebhookRouteDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('kora.register_webhook_route', false);
    }

    #[Test]
    public function webhook_route_is_absent_when_register_route_is_false(): void
    {
        $routes = collect($this->app['router']->getRoutes()->getRoutes())
            ->map(fn ($r) => $r->uri());

        self::assertFalse($routes->contains(config('kora.webhook_path')));
    }
}
