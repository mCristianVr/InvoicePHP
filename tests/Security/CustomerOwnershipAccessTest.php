<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[CoversNothing]
final class CustomerOwnershipAccessTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $this->entityManager = $this->client->getContainer()->get(EntityManagerInterface::class);

        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tables = array_map('strtolower', $schemaManager->listTableNames());

        foreach (['invoice_status_transition', 'invoice_item', 'invoice', 'customer', 'user_account'] as $table) {
            if (in_array($table, $tables, true)) {
                $connection->executeStatement('DELETE FROM ' . $table);
            }
        }
    }

    public function testUserCannotReadEditOrDeleteAnotherUsersCustomerById(): void
    {
        $userA = new User('owner-a@example.com', 'password-not-used');
        $userB = new User('owner-b@example.com', 'password-not-used');
        $customerB = new Customer($userB, 'Cliente B', 'A58818501');
        $customerB->updateDetails('Cliente B', 'A58818501', 'Calle B 2', '28001', 'Madrid', 'Madrid', 'b@example.com', '900000000');

        $this->entityManager->persist($userA);
        $this->entityManager->persist($userB);
        $this->entityManager->persist($customerB);
        $this->entityManager->flush();

        $this->client->loginUser($userA);

        $this->client->request('GET', '/customers/' . $customerB->id);
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/customers/' . $customerB->id . '/edit');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('POST', '/customers/' . $customerB->id . '/delete', [
            '_csrf_token' => 'fake-token',
        ]);
        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminCanViewOtherUsersCustomersAsExplicitException(): void
    {
        $admin = new User('admin@example.com', 'password-not-used');
        $admin->replaceManageableRoles(['ROLE_ADMIN']);

        $owner = new User('owner@example.com', 'password-not-used');
        $customer = new Customer($owner, 'Cliente Uno', 'X1234567L');

        $this->entityManager->persist($admin);
        $this->entityManager->persist($owner);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        $this->client->loginUser($admin);

        $this->client->request('GET', '/customers/' . $customer->id);
        self::assertResponseIsSuccessful();
    }
}
