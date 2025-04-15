<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function array_intersect;
use function array_keys;
use function current;
use function str_split;
use function strlen;
use function substr;

final class Royalmail implements AdapterInterface
{
    private const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /** @var array<array-key, int> */
    private const ROWS = [
        '0' => 1,
        '1' => 1,
        '2' => 1,
        '3' => 1,
        '4' => 1,
        '5' => 1,
        '6' => 2,
        '7' => 2,
        '8' => 2,
        '9' => 2,
        'A' => 2,
        'B' => 2,
        'C' => 3,
        'D' => 3,
        'E' => 3,
        'F' => 3,
        'G' => 3,
        'H' => 3,
        'I' => 4,
        'J' => 4,
        'K' => 4,
        'L' => 4,
        'M' => 4,
        'N' => 4,
        'O' => 5,
        'P' => 5,
        'Q' => 5,
        'R' => 5,
        'S' => 5,
        'T' => 5,
        'U' => 0,
        'V' => 0,
        'W' => 0,
        'X' => 0,
        'Y' => 0,
        'Z' => 0,
    ];

    /** @var array<array-key, int> */
    private const COLUMNS = [
        '0' => 1,
        '1' => 2,
        '2' => 3,
        '3' => 4,
        '4' => 5,
        '5' => 0,
        '6' => 1,
        '7' => 2,
        '8' => 3,
        '9' => 4,
        'A' => 5,
        'B' => 0,
        'C' => 1,
        'D' => 2,
        'E' => 3,
        'F' => 4,
        'G' => 5,
        'H' => 0,
        'I' => 1,
        'J' => 2,
        'K' => 3,
        'L' => 4,
        'M' => 5,
        'N' => 0,
        'O' => 1,
        'P' => 2,
        'Q' => 3,
        'R' => 4,
        'S' => 5,
        'T' => 0,
        'U' => 1,
        'V' => 2,
        'W' => 3,
        'X' => 4,
        'Y' => 5,
        'Z' => 0,
    ];

    /**
     * Validates the checksum ()
     */
    private static function checksum(string $value): bool
    {
        $checksum = substr($value, -1, 1);
        $values   = str_split(substr($value, 0, -1));
        $rowvalue = 0;
        $colvalue = 0;
        foreach ($values as $row) {
            $rowvalue += self::ROWS[$row];
            $colvalue += self::COLUMNS[$row];
        }

        $rowvalue %= 6;
        $colvalue %= 6;

        $rowchkvalue = array_keys(self::ROWS, $rowvalue);
        $colchkvalue = array_keys(self::COLUMNS, $colvalue);
        $intersect   = array_intersect($rowchkvalue, $colchkvalue);
        $chkvalue    = (string) current($intersect);

        if ($chkvalue === $checksum) {
            return true;
        }

        return false;
    }

    /**
     * Allows start and stop tag within checked chars
     */
    public function hasValidCharacters(string $value): bool
    {
        if ($value[0] === '(') {
            $value = substr($value, 1);

            if ($value[strlen($value) - 1] === ')') {
                $value = substr($value, 0, -1);
            } else {
                return false;
            }
        }

        return Util::stringMatchesAlphabet($value, self::ALPHABET);
    }

    public function hasValidLength(string $value): bool
    {
        return true;
    }

    public function hasValidChecksum(string $value): bool
    {
        return self::checksum($value);
    }

    public function getLength(): int
    {
        return -1;
    }
}
