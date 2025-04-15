<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function array_search;
use function assert;
use function count;
use function sprintf;
use function str_split;
use function substr;

final class Code93 implements AdapterInterface
{
    /**
     * Note that the characters !"§& are only synonyms
     */
    private const CHECK = [
        '0' => 0,
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
        '6' => 6,
        '7' => 7,
        '8' => 8,
        '9' => 9,
        'A' => 10,
        'B' => 11,
        'C' => 12,
        'D' => 13,
        'E' => 14,
        'F' => 15,
        'G' => 16,
        'H' => 17,
        'I' => 18,
        'J' => 19,
        'K' => 20,
        'L' => 21,
        'M' => 22,
        'N' => 23,
        'O' => 24,
        'P' => 25,
        'Q' => 26,
        'R' => 27,
        'S' => 28,
        'T' => 29,
        'U' => 30,
        'V' => 31,
        'W' => 32,
        'X' => 33,
        'Y' => 34,
        'Z' => 35,
        '-' => 36,
        '.' => 37,
        ' ' => 38,
        '$' => 39,
        '/' => 40,
        '+' => 41,
        '%' => 42,
        '!' => 43,
        '"' => 44,
        '§' => 45,
        '&' => 46,
    ];

    private const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ -.$/+%';

    /**
     * Validates the checksum (Modulo CK)
     */
    private static function checkCode93(string $value): bool
    {
        $checksum = substr($value, -2, 2);
        $value    = str_split(substr($value, 0, -2));
        $count    = 0;
        $length   = count($value) % 20;
        foreach ($value as $char) {
            if ($length === 0) {
                $length = 20;
            }

            $count += self::CHECK[$char] * $length;
            --$length;
        }

        $check = array_search($count % 47, self::CHECK);
        assert($check !== false);
        $value[] = $check;
        $count   = 0;
        $length  = count($value) % 15;
        foreach ($value as $char) {
            if ($length === 0) {
                $length = 15;
            }

            $count += self::CHECK[$char] * $length;
            --$length;
        }
        $sum = array_search($count % 47, self::CHECK);
        assert($sum !== false);
        $check = sprintf('%s%s', $check, $sum);

        if ($check === $checksum) {
            return true;
        }

        return false;
    }

    public function hasValidLength(string $value): bool
    {
        return true;
    }

    public function hasValidCharacters(string $value): bool
    {
        return Util::stringMatchesAlphabet($value, self::ALPHABET);
    }

    public function hasValidChecksum(string $value): bool
    {
        return self::checkCode93($value);
    }

    public function getLength(): int
    {
        return -1;
    }
}
