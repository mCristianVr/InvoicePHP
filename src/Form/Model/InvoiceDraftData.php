<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;

final class InvoiceDraftData
{
    #[Assert\NotNull(message: 'La fecha del trabajo es obligatoria.')]
    public ?\DateTimeImmutable $jobDate = null;

    public ?Customer $customer = null;

    public bool $createNewCustomer = false;

    #[Assert\Length(max: 255)]
    public ?string $newCustomerName = null;

    #[Assert\Length(max: 20)]
    public ?string $newCustomerNifCif = null;

    #[Assert\Email(message: 'El email no tiene un formato valido.')]
    #[Assert\Length(max: 255)]
    public ?string $newCustomerEmail = null;

    /** @var list<InvoiceDraftLineData> */
    #[Assert\Count(min: 1, minMessage: 'Debes anadir al menos una linea de concepto.')]
    #[Assert\Valid]
    public array $lines = [];

    public function __construct()
    {
        $this->jobDate = new \DateTimeImmutable('today');
        $this->lines[] = new InvoiceDraftLineData();
    }
}
