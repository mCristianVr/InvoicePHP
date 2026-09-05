<?php

declare(strict_types=1);

namespace App\Tests\Security;

use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversNothing]
final class AdminUsersAccessTest extends WebTestCase
{
    public function testGuestIsRedirectedToLoginOnAdminUsersPage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/admin/users');

        self::assertResponseStatusCodeSame(302);
        self::assertResponseRedirects('/login');
    }

    public function testAdminUsersPathRequiresAdminRoleInAccessControl(): void
    {
        static::createClient();
        $accessMap = static::getContainer()->get('security.access_map');
        [$attributes] = $accessMap->getPatterns(Request::create('/admin/users'));

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
