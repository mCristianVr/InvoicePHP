<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\InvoiceDomainException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'customer')]
#[ORM\UniqueConstraint(name: 'uniq_customer_nif_cif', columns: ['nif_cif'])]
#[ORM\Index(name: 'idx_customer_name', columns: ['name'])]
final class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    public private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public private(set) string $name;

    #[ORM\Column(name: 'nif_cif', type: Types::STRING, length: 20)]
    public private(set) string $nifCif;

    #[ORM\Column(type: Types::TEXT)]
    public private(set) string $address;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public private(set) string $email;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    public private(set) ?string $phone = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $updatedAt;

    /** @var Collection<int, Invoice> */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Invoice::class)]
    public private(set) Collection $invoices;

    public function __construct(string $name, string $nifCif, string $address, string $email, ?string $phone = null)
    {
        $this->name = trim($name);
        $this->nifCif = strtoupper(trim($nifCif));
        $this->address = trim($address);
        $this->email = strtolower(trim($email));
        $this->phone = $phone !== null ? trim($phone) : null;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->invoices = new ArrayCollection();

        $this->assertValidCustomerData();
    }

    public function updateDetails(string $name, string $address, string $email, ?string $phone = null): void
    {
        $this->name = trim($name);
        $this->address = trim($address);
        $this->email = strtolower(trim($email));
        $this->phone = $phone !== null ? trim($phone) : null;
        $this->updatedAt = new \DateTimeImmutable();

        $this->assertValidCustomerData();
    }

    public static function isValidNifCif(string $value): bool
    {
        $candidate = strtoupper(trim($value));
        if ($candidate === '') {
            return false;
        }

        if (self::isValidNif($candidate)) {
            return true;
        }

        return self::isValidCif($candidate);
    }

    private static function isValidNif(string $value): bool
    {
        if (!preg_match('/^\d{8}[A-Z]$/', $value)) {
            return false;
        }

        $digits = substr($value, 0, 8);
        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $expected = $letters[(int) $digits % 23];

        return $expected === substr($value, -1);
    }

    private static function isValidCif(string $value): bool
    {
        if (!preg_match('/^[A-Z][0-9]{7}[0-9A-Z]$/', $value)) {
            return false;
        }

        $digits = substr($value, 1, 7);
        $checkCharacter = substr($value, -1);
        $control = self::computeCifControlDigit($digits);

        if (ctype_digit($checkCharacter)) {
            return (string) $control === $checkCharacter;
        }

        $letterMap = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $expectedLetter = $letterMap[$control];

        return $expectedLetter === strtoupper($checkCharacter);
    }

    private function assertValidCustomerData(): void
    {
        if ($this->name === '') {
            throw new InvoiceDomainException('Customer name cannot be empty.');
        }

        if ($this->address === '') {
            throw new InvoiceDomainException('Customer address cannot be empty.');
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvoiceDomainException('Customer email is not valid.');
        }

        if (!self::isValidNifCif($this->nifCif)) {
            throw new InvoiceDomainException('Customer NIF/CIF is not valid.');
        }
    }

    private static function computeCifControlDigit(string $digits): int
    {
        $total = 0;

        for ($index = 0, $length = strlen($digits); $index < $length; ++$index) {
            $digit = (int) $digits[$index];
            $value = ($index % 2 === 0) ? $digit * 2 : $digit;
            $total += intdiv($value, 10) + ($value % 10);
        }

        return (10 - ($total % 10)) % 10;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
