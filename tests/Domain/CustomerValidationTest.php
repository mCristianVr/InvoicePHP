<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Validator\NifCif;
use App\Validator\SpanishTaxId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(SpanishTaxId::class)]
#[CoversClass(NifCif::class)]
final class CustomerValidationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    #[DataProvider('validTaxIds')]
    public function testValidTaxIdsPassValidation(string $taxId): void
    {
        $input = new CustomerTaxIdInput($taxId);

        self::assertTrue(SpanishTaxId::isValid($taxId));
        self::assertCount(0, $this->validator->validate($input));
    }

    #[DataProvider('invalidTaxIds')]
    public function testInvalidTaxIdsFailValidation(string $taxId): void
    {
        $input = new CustomerTaxIdInput($taxId);

        self::assertFalse(SpanishTaxId::isValid($taxId));
        self::assertGreaterThan(0, $this->validator->validate($input)->count());
    }

    /** @return array<string, array{0:string}> */
    public static function validTaxIds(): array
    {
        return [
            'nif' => ['12345678Z'],
            'nie-x' => ['X1234567L'],
            'nie-y' => ['Y1234567X'],
            'nie-z' => ['Z1234567R'],
            'cif-numeric-control' => ['A58818501'],
            'cif-letter-control' => ['P2345678C'],
            'cif-with-separators' => ['A-58818501'],
        ];
    }

    /** @return array<string, array{0:string}> */
    public static function invalidTaxIds(): array
    {
        return [
            'nif-bad-letter' => ['12345678A'],
            'nie-bad-letter' => ['X1234567A'],
            'cif-bad-control' => ['A58818500'],
            'cif-requires-letter' => ['P23456783'],
            'junk' => ['NOT-A-TAX-ID'],
        ];
    }
}

final class CustomerTaxIdInput
{
    #[NifCif]
    public string $nifCif;

    public function __construct(string $nifCif)
    {
        $this->nifCif = $nifCif;
    }
}
