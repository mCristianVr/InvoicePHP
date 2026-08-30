<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Entity\Customer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Customer::class)]
final class CustomerValidationTest extends TestCase
{
    public function testSpanishNifChecksumIsValidated(): void
    {
        self::assertTrue(Customer::isValidNifCif('12345678Z'));
        self::assertFalse(Customer::isValidNifCif('12345678A'));
    }

    public function testSpanishCifChecksumIsValidated(): void
    {
        self::assertTrue(Customer::isValidNifCif('A58818501'));
        self::assertFalse(Customer::isValidNifCif('A58818500'));
    }
}
