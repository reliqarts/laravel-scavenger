<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration\Service;

use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use ReliqArts\Scavenger\Service\ConfigProvider;
use ReliqArts\Scavenger\Tests\Fixtures\Model\HashedPasswordUser;
use ReliqArts\Scavenger\Tests\Fixtures\Model\User;
use ReliqArts\Scavenger\Tests\Integration\TestCase;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    public function test_plain_password_is_hashed_when_the_model_does_not_cast_it(): void
    {
        config(['scavenger.daemon.model' => User::class]);

        $info = $this->app->make(ConfigProvider::class)->getDaemonInfo();

        self::assertNotSame('pass', $info['password']);
        self::assertTrue(Hash::check('pass', $info['password']));
    }

    public function test_password_is_left_plain_when_the_model_uses_the_hashed_cast(): void
    {
        config(['scavenger.daemon.model' => HashedPasswordUser::class]);

        $info = $this->app->make(ConfigProvider::class)->getDaemonInfo();

        self::assertSame('pass', $info['password']);
    }

    public function test_empty_password_is_not_hashed(): void
    {
        config(['scavenger.daemon.info.password' => '']);

        $info = $this->app->make(ConfigProvider::class)->getDaemonInfo();

        self::assertSame('', $info['password']);
    }
}
