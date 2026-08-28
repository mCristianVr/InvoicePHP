<?php

declare(strict_types=1);

namespace App\Service\InvoiceXml;

use App\Entity\Invoice;

interface InvoiceXmlGeneratorInterface
{
    public function generateFacturae(Invoice $invoice): string;

    public function generateUbl(Invoice $invoice): string;
}
