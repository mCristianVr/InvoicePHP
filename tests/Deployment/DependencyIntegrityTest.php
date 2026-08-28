<?php

declare(strict_types=1);

namespace App\Tests\Deployment;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class DependencyIntegrityTest extends TestCase
{
    public function testCoreDependencyVersionsMatchExpectedMajorOrMinor(): void
    {
        $lockFilePath = dirname(__DIR__, 2).'/composer.lock';
        $lock = json_decode((string) file_get_contents($lockFilePath), true, 512, JSON_THROW_ON_ERROR);

        $versions = [];
        foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
            $versions[$package['name']] = ltrim((string) ($package['version'] ?? ''), 'v');
        }

        self::assertArrayHasKey('symfony/framework-bundle', $versions);
        self::assertStringStartsWith('8.1.', $versions['symfony/framework-bundle']);

        self::assertArrayHasKey('doctrine/orm', $versions);
        self::assertStringStartsWith('3.', $versions['doctrine/orm']);

        self::assertArrayHasKey('doctrine/dbal', $versions);
        self::assertStringStartsWith('4.', $versions['doctrine/dbal']);

        self::assertArrayHasKey('doctrine/doctrine-bundle', $versions);
        self::assertStringStartsWith('3.', $versions['doctrine/doctrine-bundle']);

        self::assertArrayHasKey('doctrine/doctrine-migrations-bundle', $versions);
        self::assertStringStartsWith('4.', $versions['doctrine/doctrine-migrations-bundle']);

        self::assertArrayHasKey('symfonycasts/tailwind-bundle', $versions);
        self::assertStringStartsWith('1.', $versions['symfonycasts/tailwind-bundle']);
    }
}
