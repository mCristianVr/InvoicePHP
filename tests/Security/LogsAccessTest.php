<?php

declare(strict_types=1);

namespace App\Tests\Security;

use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversNothing]
final class LogsAccessTest extends WebTestCase
{
    public function testGuestIsRedirectedToLoginOnLogsPage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/logs');

        self::assertResponseStatusCodeSame(302);
        self::assertResponseRedirects('/login');
    }

    public function testLogsPathRequiresAdminRoleInAccessControl(): void
    {
        static::createClient();
        $accessMap = static::getContainer()->get('security.access_map');
        [$attributes] = $accessMap->getPatterns(Request::create('/logs'));

        self::assertIsArray($attributes);

        $roles = [];
        foreach ($attributes as $attribute) {
            if (is_string($attribute)) {
                $roles[] = $attribute;
                continue;
            }

            if (is_object($attribute) && method_exists($attribute, 'getRole')) {
                $roles[] = (string) $attribute->getRole();
            }
        }

        self::assertContains('ROLE_ADMIN', $roles);
    }
}
