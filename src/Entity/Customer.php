<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\NifCif;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Table(name: 'customer')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uniq_customer_owner_nif_cif', columns: ['owner_id', 'nif_cif'])]
#[ORM\Index(name: 'idx_customer_owner_name', columns: ['owner_id', 'name'])]
final class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    public private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'El nombre es obligatorio.')]
    #[Assert\Length(max: 255)]
    public private(set) string $name;

    #[ORM\Column(name: 'nif_cif', type: Types::STRING, length: 20)]
    #[Assert\NotBlank(message: 'El NIF/CIF es obligatorio.')]
    #[NifCif]
    public private(set) string $nifCif;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 4000)]
    public private(set) ?string $address = null;

    #[ORM\Column(name: 'postal_code', type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    public private(set) ?string $postalCode = null;

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true)]
    #[Assert\Length(max: 120)]
    public private(set) ?string $city = null;

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true)]
    #[Assert\Length(max: 120)]
    public private(set) ?string $province = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Email(message: 'El email no tiene un formato valido.')]
    #[Assert\Length(max: 255)]
    public private(set) ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    public private(set) ?string $phone = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false)]
    public private(set) User $owner;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $updatedAt;

    /** @var Collection<int, Invoice> */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Invoice::class)]
    public private(set) Collection $invoices;

    public function __construct(User $owner, string $name, string $nifCif)
    {
        $this->owner = $owner;
        $this->name = trim($name);
        $this->nifCif = strtoupper(trim($nifCif));
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->invoices = new ArrayCollection();
    }

    public function updateDetails(
        string $name,
        string $nifCif,
        ?string $address,
        ?string $postalCode,
        ?string $city,
        ?string $province,
        ?string $email,
        ?string $phone,
    ): void
    {
        $this->name = trim($name);
        $this->nifCif = strtoupper(trim($nifCif));
        $this->address = self::trimOrNull($address);
        $this->postalCode = self::trimOrNull($postalCode);
        $this->city = self::trimOrNull($city);
        $this->province = self::trimOrNull($province);
        $this->email = self::normalizeEmail($email);
        $this->phone = self::trimOrNull($phone);
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function touch(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $this->createdAt ?? $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private static function trimOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function normalizeEmail(?string $value): ?string
    {
        $trimmed = self::trimOrNull($value);

        return $trimmed !== null ? strtolower($trimmed) : null;
    }
}
