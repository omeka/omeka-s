<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function assert;
use function chr;
use function is_numeric;
use function str_replace;
use function str_split;
use function strlen;
use function substr;

/** @internal */
final class Util
{
    public static function stringMatchesAlphabet(string $value, string $alphabet): bool
    {
        $chars = str_split($alphabet);
        foreach ($chars as $char) {
            $value = str_replace($char, '', $value);
        }

        return strlen($value) === 0;
    }

    public static function isAscii128(string $value): bool
    {
        for ($x = 0; $x < 128; ++$x) {
            $value = str_replace(chr($x), '', $value);
        }

        return strlen($value) === 0;
    }

    /**
     * Validates the checksum (Modulo 10)
     * GTIN implementation factor 3
     */
    public static function gtin(string $value): bool
    {
        $barcode = substr($value, 0, -1);
        $sum     = 0;
        $length  = strlen($barcode) - 1;

        for ($i = 0; $i <= $length; $i++) {
            $chr = $barcode[$length - $i];
            assert(is_numeric($chr));
            if (($i % 2) === 0) {
                $sum += $chr * 3;
            } else {
                $sum += $chr;
            }
        }

        $calc     = $sum % 10;
        $checksum = $calc === 0 ? 0 : 10 - $calc;

        return $value[$length + 1] === (string) $checksum;
    }

    /**
     * Validates the checksum (Modulo 10)
     * IDENTCODE implementation factors 9 and 4
     */
    public static function identcode(string $value): bool
    {
        $barcode = substr($value, 0, -1);
        $sum     = 0;
        $length  = strlen($value) - 2;

        for ($i = 0; $i <= $length; $i++) {
            $chr = $barcode[$length - $i];
            assert(is_numeric($chr));

            if (($i % 2) === 0) {
                $sum += $chr * 4;
            } else {
                $sum += $chr * 9;
            }
        }

        $calc     = $sum % 10;
        $checksum = $calc === 0 ? 0 : 10 - $calc;

        return $value[$length + 1] === (string) $checksum;
    }

    /**
     * Validates the checksum ()
     * POSTNET implementation
     */
    public static function postnet(string $value): bool
    {
        $checksum = substr($value, -1, 1);
        $values   = str_split(substr($value, 0, -1));

        $check = 0;
        foreach ($values as $row) {
            assert(is_numeric($row));
            $check += $row;
        }

        $check %= 10;
        $check  = 10 - $check;

        return (string) $check === $checksum;
    }

    /**
     * Validates the checksum (Modulo 10)
     * CODE25 implementation factor 3
     */
    public static function code25(string $value): bool
    {
        $barcode = substr($value, 0, -1);
        $sum     = 0;
        $length  = strlen($barcode) - 1;

        for ($i = 0; $i <= $length; $i++) {
            $chr = $barcode[$i];
            assert(is_numeric($chr));
            if (($i % 2) === 0) {
                $sum += $chr * 3;
            } else {
                $sum += $chr;
            }
        }

        $calc     = $sum % 10;
        $checksum = $calc === 0 ? 0 : 10 - $calc;

        return $value[$length + 1] === (string) $checksum;
    }
}
