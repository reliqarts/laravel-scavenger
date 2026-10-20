<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;
use ReliqArts\Scavenger\Contract\ConfigProvider;
use ReliqArts\Scavenger\ServiceProvider;

#[CoversClass(ServiceProvider::class)]
final class HttpClientOptionsTest extends TestCase
{
    public function test_timeout_is_applied_as_idle_and_total_cap(): void
    {
        config(['scavenger.guzzle_settings.timeout' => 15]);

        self::assertSame(
            ['timeout' => 15.0, 'max_duration' => 15.0],
            $this->httpClientOptions(),
        );
    }

    public function test_missing_or_invalid_timeout_falls_back_to_sixty_seconds(): void
    {
        config(['scavenger.guzzle_settings' => []]);

        self::assertSame(
            ['timeout' => 60.0, 'max_duration' => 60.0],
            $this->httpClientOptions(),
        );

        config(['scavenger.guzzle_settings.timeout' => 'slow']);

        self::assertSame(
            ['timeout' => 60.0, 'max_duration' => 60.0],
            $this->httpClientOptions(),
        );
    }

    /**
     * @return array{timeout: float, max_duration: float}
     */
    private function httpClientOptions(): array
    {
        $provider = new ServiceProvider($this->app);
        $method = new ReflectionMethod(ServiceProvider::class, 'httpClientOptions');
        $options = $method->invoke($provider, $this->app->make(ConfigProvider::class));

        self::assertIsArray($options);

        return $options;
    }
}
