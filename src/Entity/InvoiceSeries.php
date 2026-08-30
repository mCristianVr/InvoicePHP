<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\InvoiceDomainException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'invoice_series')]
#[ORM\UniqueConstraint(name: 'uniq_invoice_series_prefix_year', columns: ['prefix', 'year'])]
final class InvoiceSeries
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    public private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    public private(set) string $prefix;

    #[ORM\Column(type: Types::INTEGER)]
    public private(set) int $year;

    #[ORM\Column(name: 'next_number', type: Types::BIGINT)]
    public private(set) int $nextNumber = 1;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    public function __construct(string $prefix, int $year, int $nextNumber = 1)
    {
        $this->prefix = strtoupper(trim($prefix));
        $this->year = $year;
        $this->nextNumber = $nextNumber;
        $this->createdAt = new \DateTimeImmutable();

        if ($this->prefix === '') {
            throw new InvoiceDomainException('Invoice series prefix cannot be empty.');
        }

        if ($this->year < 2000 || $this->year > 2100) {
            throw new InvoiceDomainException('Invoice series year must be a plausible calendar year.');
        }

        if ($this->nextNumber < 1) {
            throw new InvoiceDomainException('The next invoice number must be greater than zero.');
        }
    }

    public function nextNumber(): int
    {
        $current = $this->nextNumber;
        $this->nextNumber += 1;

        return $current;
    }

    public function formatNumber(int $number): string
    {
        return sprintf('%s-%d-%06d', $this->prefix, $this->year, $number);
    }
}
