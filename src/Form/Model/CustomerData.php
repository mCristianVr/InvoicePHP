<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Customer;
use App\Validator\NifCif;
use Symfony\Component\Validator\Constraints as Assert;

final class CustomerData
{
    #[Assert\NotBlank(message: 'El nombre es obligatorio.')]
    #[Assert\Length(max: 255)]
    public string $name = '';

    #[Assert\NotBlank(message: 'El NIF/CIF es obligatorio.')]
    #[Assert\Length(max: 20)]
    #[NifCif]
    public string $nifCif = '';

    #[Assert\Length(max: 4000)]
    public ?string $address = null;

    #[Assert\Length(max: 20)]
    public ?string $postalCode = null;

    #[Assert\Length(max: 120)]
    public ?string $city = null;

    #[Assert\Length(max: 120)]
    public ?string $province = null;

    #[Assert\Email(message: 'El email no tiene un formato valido.')]
    #[Assert\Length(max: 255)]
    public ?string $email = null;

    #[Assert\Length(max: 30)]
    public ?string $phone = null;

    public static function fromEntity(Customer $customer): self
    {
        $data = new self();
        $data->name = $customer->name;
        $data->nifCif = $customer->nifCif;
        $data->address = $customer->address;
        $data->postalCode = $customer->postalCode;
        $data->city = $customer->city;
        $data->province = $customer->province;
        $data->email = $customer->email;
        $data->phone = $customer->phone;

        return $data;
    }
}
