<?php

declare(strict_types=1);

namespace App\Validator;

final class SpanishTaxId
{
    private const NIF_CONTROL_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';
    private const CIF_CONTROL_LETTERS = 'JABCDEFGHI';

    public static function isValid(string $value): bool
    {
        $candidate = self::normalize($value);
        if ($candidate === '') {
            return false;
        }

        return self::isValidNif($candidate)
            || self::isValidNie($candidate)
            || self::isValidCif($candidate);
    }

    private static function normalize(string $value): string
    {
        $upper = strtoupper(trim($value));

        return preg_replace('/[\s.-]+/', '', $upper) ?? '';
    }

    private static function isValidNif(string $value): bool
    {
        if (!preg_match('/^(\d{8})([A-Z])$/', $value, $matches)) {
            return false;
        }

        $number = (int) $matches[1];
        $letter = $matches[2];

        return self::nifControlLetter($number) === $letter;
    }

    private static function isValidNie(string $value): bool
    {
        if (!preg_match('/^([XYZ])(\d{7})([A-Z])$/', $value, $matches)) {
            return false;
        }

        $prefix = match ($matches[1]) {
            'X' => '0',
            'Y' => '1',
            'Z' => '2',
            default => '',
        };

        if ($prefix === '') {
            return false;
        }

        $number = (int) ($prefix . $matches[2]);
        $letter = $matches[3];

        return self::nifControlLetter($number) === $letter;
    }

    private static function isValidCif(string $value): bool
    {
        if (!preg_match('/^([ABCDEFGHJNPQRSUVW])(\d{7})([0-9A-J])$/', $value, $matches)) {
            return false;
        }

        $entityType = $matches[1];
        $digits = $matches[2];
        $providedControl = $matches[3];
        $computedControlDigit = self::computeCifControlDigit($digits);
        $computedControlLetter = self::CIF_CONTROL_LETTERS[$computedControlDigit];

        if (str_contains('ABEH', $entityType)) {
            return (string) $computedControlDigit === $providedControl;
        }

        if (str_contains('KPQS', $entityType)) {
            return $computedControlLetter === $providedControl;
        }

        return (string) $computedControlDigit === $providedControl || $computedControlLetter === $providedControl;
    }

    private static function nifControlLetter(int $number): string
    {
        return self::NIF_CONTROL_LETTERS[$number % 23];
    }

    private static function computeCifControlDigit(string $digits): int
    {
        $oddSum = 0;
        $evenSum = 0;

        for ($index = 0; $index < strlen($digits); ++$index) {
            $digit = (int) $digits[$index];

            if ($index % 2 === 0) {
                $doubled = $digit * 2;
                $oddSum += intdiv($doubled, 10) + ($doubled % 10);
                continue;
            }

            $evenSum += $digit;
        }

        $total = $oddSum + $evenSum;

        return (10 - ($total % 10)) % 10;
    }
}
