<?php

declare(strict_types=1);

namespace App\Service\InvoiceXml;

use App\Entity\Invoice;

final class NullInvoiceXmlGenerator implements InvoiceXmlGeneratorInterface
{
    public function generateFacturae(Invoice $invoice): string
    {
        throw new \LogicException('Facturae generation is not implemented yet.');
    }

    public function generateUbl(Invoice $invoice): string
    {
        throw new \LogicException('UBL generation is not implemented yet.');
    }
}
