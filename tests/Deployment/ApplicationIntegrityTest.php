<?php

declare(strict_types=1);

namespace App\Tests\Deployment;

use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\InvoiceRectification;
use App\Entity\InvoiceStatusTransition;
use App\Service\InvoiceXml\InvoiceXmlGeneratorInterface;
use App\Service\InvoiceXml\NullInvoiceXmlGenerator;
use App\Service\VeriFactu\VeriFactuChainingService;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversNothing]
final class ApplicationIntegrityTest extends KernelTestCase
{
    public function testCriticalServicesAreResolvable(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        self::assertInstanceOf(VeriFactuChainingService::class, $container->get(VeriFactuChainingService::class));
        $xmlGenerator = $container->get(InvoiceXmlGeneratorInterface::class);
        self::assertInstanceOf(NullInvoiceXmlGenerator::class, $xmlGenerator);
    }

    public function testDoctrineMappingContainsCoreEntities(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $registry = $container->get(ManagerRegistry::class);
        $entityManager = $registry->getManager();
        $metadataFactory = $entityManager->getMetadataFactory();

        self::assertSame(Invoice::class, $metadataFactory->getMetadataFor(Invoice::class)->getName());
        self::assertSame(InvoiceItem::class, $metadataFactory->getMetadataFor(InvoiceItem::class)->getName());
        self::assertSame(InvoiceStatusTransition::class, $metadataFactory->getMetadataFor(InvoiceStatusTransition::class)->getName());
        self::assertSame(InvoiceRectification::class, $metadataFactory->getMetadataFor(InvoiceRectification::class)->getName());
    }
}
