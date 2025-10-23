<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Providers\EventServiceProvider;

class EventServiceProviderTest extends TestCase
{
    public function test_event_service_provider_registers_events()
    {
        $provider = new EventServiceProvider($this->app);

        $reflection = new \ReflectionClass($provider);
        $property = $reflection->getProperty('listen');
        $property->setAccessible(true);

        $listen = $property->getValue($provider);

        $this->assertIsArray($listen);
        $this->assertArrayHasKey(\App\Events\TransactionCreated::class, $listen);
    }
}
