<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversNothing]
final class InvoiceDraftCreationTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get(EntityManagerInterface::class);

        $connection = $this->entityManager->getConnection();
        $tables = array_map('strtolower', $connection->createSchemaManager()->listTableNames());

        foreach (['invoice_status_transition', 'invoice_item', 'invoice', 'customer', 'user_account'] as $table) {
            if (in_array($table, $tables, true)) {
                $connection->executeStatement('DELETE FROM ' . $table);
            }
        }
    }

    public function testGuestIsRedirectedFromInvoiceCreatePage(): void
    {
        $this->client->request('GET', '/invoices/new');

        self::assertResponseStatusCodeSame(302);
        self::assertResponseRedirects('/login');
    }

    public function testUserCannotForgeAnotherOwnersCustomerIdWhenCreatingDraftInvoice(): void
    {
        $userA = new User('invoice-a@example.com', 'password-not-used');
        $userB = new User('invoice-b@example.com', 'password-not-used');

        $customerA = new Customer($userA, 'Cliente de A', 'X1234567L');
        $customerA->updateDetails('Cliente de A', 'X1234567L', 'Calle A 1', '28001', 'Madrid', 'Madrid', 'a@example.com', '911111111');

        $customerB = new Customer($userB, 'Cliente de B', 'A58818501');
        $customerB->updateDetails('Cliente de B', 'A58818501', 'Calle B 7', '08001', 'Barcelona', 'Barcelona', 'b@example.com', '900000000');

        $this->entityManager->persist($userA);
        $this->entityManager->persist($userB);
        $this->entityManager->persist($customerA);
        $this->entityManager->persist($customerB);
        $this->entityManager->flush();

        $this->client->loginUser($userA);
        $crawler = $this->client->request('GET', '/invoices/new');
        $csrfToken = (string) $crawler->filter('input[name="invoice_draft[_token]"]')->attr('value');

        $this->client->request('POST', '/invoices/new', [
            'invoice_draft' => [
                '_token' => $csrfToken,
                'jobDate' => '2026-09-06',
                'customerId' => (string) $customerB->id,
                'createNewCustomer' => '0',
                'lines' => [
                    [
                        'description' => 'Servicio mensual',
                        'quantity' => '1',
                        'unitPrice' => '100.00',
                        'taxRatePercent' => '21.00',
                    ],
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(422);

        $count = (int) $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM invoice');
        self::assertSame(0, $count);
    }

    public function testUserCanCreateDraftInvoiceForOwnedCustomer(): void
    {
        $user = new User('invoice-owner@example.com', 'password-not-used');
        $customer = new Customer($user, 'Cliente Propio', 'X1234567L');
        $customer->updateDetails('Cliente Propio', 'X1234567L', 'Calle Mayor 1', '28013', 'Madrid', 'Madrid', 'owner@example.com', '911111111');

        $this->entityManager->persist($user);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/invoices/new');

        $form = $crawler->selectButton('Crear borrador')->form([
            'invoice_draft[jobDate]' => '2026-09-06',
            'invoice_draft[customerId]' => (string) $customer->id,
            'invoice_draft[lines][0][description]' => 'Diseño web',
            'invoice_draft[lines][0][quantity]' => '2',
            'invoice_draft[lines][0][unitPrice]' => '250.00',
            'invoice_draft[lines][0][taxRatePercent]' => '21.00',
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/dashboard');

        $this->entityManager->clear();
        $invoice = $this->entityManager->getRepository(Invoice::class)->findOneBy([]);

        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertSame('DRAFT', $invoice->status->value);
        self::assertNotNull($invoice->customer);
        self::assertSame($customer->id, $invoice->customer->id);
        self::assertSame(50000, $invoice->subtotalCents);
        self::assertSame(10500, $invoice->taxTotalCents);
        self::assertSame(60500, $invoice->grandTotalCents);
    }

    public function testUserCanCreateDraftInvoiceAndInlineCustomerInSameForm(): void
    {
        $user = new User('invoice-inline@example.com', 'password-not-used');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/invoices/new');

        $form = $crawler->selectButton('Crear borrador')->form([
            'invoice_draft[jobDate]' => '2026-09-06',
            'invoice_draft[createNewCustomer]' => '1',
            'invoice_draft[newCustomerName]' => 'Cliente Inline',
            'invoice_draft[newCustomerNifCif]' => 'A58818501',
            'invoice_draft[newCustomerEmail]' => 'inline@example.com',
            'invoice_draft[lines][0][description]' => 'Mantenimiento mensual',
            'invoice_draft[lines][0][quantity]' => '1',
            'invoice_draft[lines][0][unitPrice]' => '300.00',
            'invoice_draft[lines][0][taxRatePercent]' => '21.00',
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/dashboard');

        $this->entityManager->clear();
        $invoice = $this->entityManager->getRepository(Invoice::class)->findOneBy([], ['id' => 'DESC']);

        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertNotNull($invoice->customer);
        self::assertSame('Cliente Inline', $invoice->customer->name);
        self::assertSame('A58818501', $invoice->customer->nifCif);
    }
}
