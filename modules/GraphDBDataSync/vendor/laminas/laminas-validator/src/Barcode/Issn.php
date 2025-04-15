<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function assert;
use function in_array;
use function is_numeric;
use function str_contains;
use function str_split;
use function strlen;
use function substr;

final class Issn implements AdapterInterface
{
    private const LENGTH   = [8, 13];
    private const ALPHABET = '0123456789X';

    /**
     * Allows X on length of 8 chars
     */
    public function hasValidCharacters(string $value): bool
    {
        if (strlen($value) !== 8) {
            if (str_contains($value, 'X')) {
                return false;
            }
        }

        return Util::stringMatchesAlphabet($value, self::ALPHABET);
    }

    public function hasValidLength(string $value): bool
    {
        return in_array(strlen($value), self::LENGTH, true);
    }

    public function getLength(): array
    {
        return self::LENGTH;
    }

    public function hasValidChecksum(string $value): bool
    {
        if (strlen($value) === 8) {
            return self::issnCheck($value);
        }

        return Util::gtin($value);
    }

    /**
     * Validates the checksum ()
     * ISSN implementation (reversed mod11)
     */
    private static function issnCheck(string $value): bool
    {
        $checksum = substr($value, -1, 1);
        $values   = str_split(substr($value, 0, -1));
        $check    = 0;
        $multi    = 8;
        foreach ($values as $token) {
            if ($token === 'X') {
                $token = 10;
            }

            assert(is_numeric($token));
            $check += $token * $multi;
            --$multi;
        }

        $check %= 11;
        $check  = $check === 0 ? 0 : 11 - $check;

        if ((string) $check === $checksum) {
            return true;
        }

        if (($check === 10) && ($checksum === 'X')) {
            return true;
        }

        return false;
    }
}
