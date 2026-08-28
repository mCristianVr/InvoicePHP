<?php

declare(strict_types=1);

namespace App\Tests\Deployment;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class RuntimeIntegrityTest extends TestCase
{
    public function testRuntimeUsesPhp85OrHigher(): void
    {
        self::assertGreaterThanOrEqual(
            80_500,
            PHP_VERSION_ID,
            sprintf('Runtime PHP must be >= 8.5. Current: %s', PHP_VERSION),
        );
    }

    public function testProjectRequiresPhp85OrHigherInComposerManifest(): void
    {
        $composerJsonPath = dirname(__DIR__, 2).'/composer.json';
        $composer = json_decode((string) file_get_contents($composerJsonPath), true, 512, JSON_THROW_ON_ERROR);

        $phpConstraint = $composer['require']['php'] ?? null;

        self::assertSame('>=8.5', $phpConstraint);
    }
}
