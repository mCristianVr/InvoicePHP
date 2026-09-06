<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class InvoiceDraftLineData
{
    #[Assert\NotBlank(message: 'La descripcion de la linea es obligatoria.')]
    #[Assert\Length(max: 255)]
    public string $description = '';

    #[Assert\Positive(message: 'La cantidad debe ser mayor que 0.')]
    public int $quantity = 1;

    #[Assert\NotNull(message: 'El precio unitario es obligatorio.')]
    #[Assert\GreaterThanOrEqual(0, message: 'El precio unitario no puede ser negativo.')]
    public ?float $unitPrice = null;

    #[Assert\NotNull(message: 'El IVA es obligatorio.')]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'El IVA debe estar entre {{ min }} y {{ max }} %.')]
    public ?float $taxRatePercent = 21.0;
}
