<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use Laminas\Stdlib\StringUtils;
use Laminas\Stdlib\StringWrapper\StringWrapperInterface;

use function assert;
use function chr;
use function is_string;
use function ord;

final class Code128 implements AdapterInterface
{
    private readonly StringWrapperInterface $utf8StringWrapper;

    public function __construct()
    {
        $this->utf8StringWrapper = StringUtils::getWrapper('UTF-8');
    }

    public function hasValidLength(string $value): bool
    {
        return true;
    }

    public function hasValidChecksum(string $value): bool
    {
        return $this->code128($value);
    }

    public function getLength(): int
    {
        return -1;
    }

    /**
     * Checks for allowed characters within the barcode
     */
    public function hasValidCharacters(string $value): bool
    {
        // get used string wrapper for UTF-8 character encoding
        $strWrapper = $this->utf8StringWrapper;

        // detect starting charset
        $set  = $this->getCodingSet($value);
        $read = $set;
        if ($set !== '') {
            $value = $strWrapper->substr($value, 1, null);
        }

        // process barcode
        while ($value !== '' && $value !== false) {
            $char = $strWrapper->substr($value, 0, 1);
            assert(is_string($char));

            switch ($char) {
                // Function definition
                case 'Ç':
                case 'ü':
                case 'å':
                case 'é':
                    break;

                // Switch to C
                case 'â':
                    $set = 'C';
                    break;

                // Switch to B
                case 'ä':
                    $set = 'B';
                    break;

                // Switch to A
                case 'à':
                    $set = 'A';
                    break;

                // Doubled start character
                case '‡':
                case 'ˆ':
                case '‰':
                    return false;

                // Chars after the stop character
                case 'Š':
                    break 2;

                default:
                    // Does the char exist within the charset to read?
                    if ($this->ord128($char, $read) === -1) {
                        return false;
                    }

                    break;
            }

            $value = $strWrapper->substr($value, 1, null);
            $read  = $set;
        }

        if ($value !== '' && is_string($value) && $strWrapper->strlen($value) !== 1) {
            return false;
        }

        return true;
    }

    /**
     * Validates the checksum
     */
    private function code128(string $value): bool
    {
        $pos        = 1;
        $set        = $this->getCodingSet($value);
        $read       = $set;
        $strWrapper = $this->utf8StringWrapper;
        $char       = $strWrapper->substr($value, 0, 1);
        if ($char === '‡') {
            $sum = 103;
        } elseif ($char === 'ˆ') {
            $sum = 104;
        } elseif ($char === '‰') {
            $sum = 105;
        } else {
            // no start value, unable to detect a proper checksum
            return false;
        }

        $value = $strWrapper->substr($value, 1, null);
        assert($value !== false);
        while ($strWrapper->strpos($value, 'Š') !== false || ($value !== '')) {
            $char = $strWrapper->substr($value, 0, 1);
            if ($read === 'C') {
                $char = $strWrapper->substr($value, 0, 2);
            }
            assert($char !== false);

            switch ($char) {
                // Function definition
                case 'Ç':
                case 'ü':
                case 'å':
                case 'é':
                    $sum += $pos * $this->ord128($char, $set);
                    break;

                // Switch to C
                case 'â':
                    $sum += $pos * $this->ord128($char, $set);
                    $set  = 'C';
                    break;

                // Switch to B
                case 'ä':
                    $sum += $pos * $this->ord128($char, $set);
                    $set  = 'B';
                    break;

                // Switch to A
                case 'à':
                    $sum += $pos * $this->ord128($char, $set);
                    $set  = 'A';
                    break;

                case '‡':
                case 'ˆ':
                case '‰':
                    return false;

                default:
                    // Does the char exist within the charset to read?
                    if ($this->ord128($char, $read) === -1) {
                        return false;
                    }

                    $sum += $pos * $this->ord128($char, $set);
                    break;
            }

            $value = $strWrapper->substr($value, 1);
            assert($value !== false);
            ++$pos;
            if (($strWrapper->strpos($value, 'Š') === 1) && ($strWrapper->strlen($value) === 2)) {
                // break by stop and checksum char
                break;
            }
            $read = $set;
        }

        if (($strWrapper->strpos($value, 'Š') !== 1) || ($strWrapper->strlen($value) !== 2)) {
            // return false if checksum is not readable and true if no startvalue is detected
            return false;
        }

        $mod = $sum % 103;
        if ($strWrapper->substr($value, 0, 1) === $this->chr128($mod, $set)) {
            return true;
        }

        return false;
    }

    /**
     * Returns the coding set for a barcode
     */
    private function getCodingSet(string $value): string
    {
        $value = $this->utf8StringWrapper->substr($value, 0, 1);

        return match ($value) {
            '‡' => 'A',
            'ˆ' => 'B',
            '‰' => 'C',
            default => '',
        };
    }

    /**
     * Internal method to return the code128 integer from an ascii value
     *
     * Table A
     *    ASCII       CODE128
     *  32 to  95 ==  0 to  63
     *   0 to  31 == 64 to  95
     * 128 to 138 == 96 to 106
     *
     * Table B
     *    ASCII       CODE128
     *  32 to 138 == 0 to 106
     *
     * Table C
     *    ASCII       CODE128
     *  "00" to "99" ==   0 to  99
     *   132 to  138 == 100 to 106
     */
    private function ord128(string $value, string $set): int
    {
        $ord = ord($value);
        if ($set === 'A') {
            if ($ord < 32) {
                return $ord + 64;
            } elseif ($ord < 96) {
                return $ord - 32;
            } elseif ($ord > 138) {
                return -1;
            } else {
                return $ord - 32;
            }
        } elseif ($set === 'B') {
            if ($ord < 32) {
                return -1;
            } elseif ($ord <= 138) {
                return $ord - 32;
            } else {
                return -1;
            }
        } elseif ($set === 'C') {
            $val = (int) $value;
            if (($val >= 0) && ($val <= 99)) {
                return $val;
            } elseif (($ord >= 132) && ($ord <= 138)) {
                return $ord - 32;
            } else {
                return -1;
            }
        } else {
            if ($ord < 32) {
                return $ord + 64;
            } elseif ($ord <= 138) {
                return $ord - 32;
            } else {
                return -1;
            }
        }
    }

    /**
     * Internal Method to return the ascii value from a code128 integer
     *
     * Table A
     *    ASCII       CODE128
     *  32 to  95 ==  0 to  63
     *   0 to  31 == 64 to  95
     * 128 to 138 == 96 to 106
     *
     * Table B
     *    ASCII       CODE128
     *  32 to 138 == 0 to 106
     *
     * Table C
     *    ASCII       CODE128
     *  "00" to "99" ==   0 to  99
     *   132 to  138 == 100 to 106
     */
    private function chr128(int $value, string $set): int|string
    {
        if ($set === 'A') {
            if ($value < 64) {
                return chr($value + 32);
            } elseif ($value < 96) {
                return chr($value - 64);
            } elseif ($value > 106) {
                return -1;
            } else {
                return chr($value + 32);
            }
        } elseif ($set === 'B') {
            if ($value > 106) {
                return -1;
            } else {
                return chr($value + 32);
            }
        } elseif ($set === 'C') {
            if (($value >= 0) && ($value <= 9)) {
                return '0' . $value;
            } elseif ($value <= 99) {
                return (string) $value;
            } elseif ($value <= 106) {
                return chr($value + 32);
            } else {
                return -1;
            }
        } else {
            if ($value <= 106) {
                return $value + 32;
            } else {
                return -1;
            }
        }
    }
}
