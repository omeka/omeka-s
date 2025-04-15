<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function str_split;
use function substr;

final class Code39 implements AdapterInterface
{
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
    ];

    private const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ -.$/+%';

    /**
     * Validates the checksum (Modulo 43)
     */
    private static function checkCode39(string $value): bool
    {
        $checksum = substr($value, -1, 1);
        $value    = str_split(substr($value, 0, -1));
        $count    = 0;
        foreach ($value as $char) {
            $count += self::CHECK[$char];
        }

        $mod = $count % 43;
        if ($mod === self::CHECK[$checksum]) {
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
        return self::checkCode39($value);
    }

    public function getLength(): int
    {
        return -1;
    }
}
