<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;

use function array_key_exists;
use function in_array;
use function intval;
use function is_string;
use function preg_match;
use function str_replace;
use function strlen;
use function strtoupper;
use function substr;

/**
 * Validates IBAN Numbers (International Bank Account Numbers)
 *
 * @psalm-type OptionsArgument = array{
 *     country_code?: string,
 *     allow_non_sepa?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Iban extends AbstractValidator
{
    public const NOTSUPPORTED     = 'ibanNotSupported';
    public const SEPANOTSUPPORTED = 'ibanSepaNotSupported';
    public const FALSEFORMAT      = 'ibanFalseFormat';
    public const CHECKFAILED      = 'ibanCheckFailed';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::NOTSUPPORTED     => 'Unknown country within the IBAN',
        self::SEPANOTSUPPORTED => 'Countries outside the Single Euro Payments Area (SEPA) are not supported',
        self::FALSEFORMAT      => 'The input has a false IBAN format',
        self::CHECKFAILED      => 'The input has failed the IBAN check',
    ];

    /**
     * Optional country code by ISO 3166-1
     */
    private readonly ?string $countryCode;

    /**
     * Optionally allow IBAN codes from non-SEPA countries. Defaults to true
     */
    private readonly bool $allowNonSepa;

    /**
     * SEPA ISO 3166-1country codes
     */
    private const SEPA_COUNTRIES = [
        'AT',
        'BE',
        'BG',
        'CY',
        'CZ',
        'DK',
        'FO',
        'GL',
        'EE',
        'FI',
        'FR',
        'DE',
        'GI',
        'GR',
        'HU',
        'IS',
        'IE',
        'IT',
        'LV',
        'LI',
        'LT',
        'LU',
        'MT',
        'MC',
        'NL',
        'NO',
        'PL',
        'PT',
        'RO',
        'SK',
        'SI',
        'ES',
        'SE',
        'CH',
        'GB',
        'SM',
        'HR',
    ];

    /**
     * IBAN regexes by country code
     */
    private const IBAN_REGEX = [
        'AD' => 'AD[0-9]{2}[0-9]{4}[0-9]{4}[A-Z0-9]{12}',
        'AE' => 'AE[0-9]{2}[0-9]{3}[0-9]{16}',
        'AL' => 'AL[0-9]{2}[0-9]{8}[A-Z0-9]{16}',
        'AT' => 'AT[0-9]{2}[0-9]{5}[0-9]{11}',
        'AZ' => 'AZ[0-9]{2}[A-Z]{4}[A-Z0-9]{20}',
        'BA' => 'BA[0-9]{2}[0-9]{3}[0-9]{3}[0-9]{8}[0-9]{2}',
        'BE' => 'BE[0-9]{2}[0-9]{3}[0-9]{7}[0-9]{2}',
        'BG' => 'BG[0-9]{2}[A-Z]{4}[0-9]{4}[0-9]{2}[A-Z0-9]{8}',
        'BH' => 'BH[0-9]{2}[A-Z]{4}[A-Z0-9]{14}',
        'BR' => 'BR[0-9]{2}[0-9]{8}[0-9]{5}[0-9]{10}[A-Z][A-Z0-9]',
        'BY' => 'BY[0-9]{2}[A-Z0-9]{4}[0-9]{4}[A-Z0-9]{16}',
        'CH' => 'CH[0-9]{2}[0-9]{5}[A-Z0-9]{12}',
        'CR' => 'CR[0-9]{2}[0-9]{3}[0-9]{14}',
        'CY' => 'CY[0-9]{2}[0-9]{3}[0-9]{5}[A-Z0-9]{16}',
        'CZ' => 'CZ[0-9]{2}[0-9]{20}',
        'DE' => 'DE[0-9]{2}[0-9]{8}[0-9]{10}',
        'DO' => 'DO[0-9]{2}[A-Z0-9]{4}[0-9]{20}',
        'DK' => 'DK[0-9]{2}[0-9]{14}',
        'EE' => 'EE[0-9]{2}[0-9]{2}[0-9]{2}[0-9]{11}[0-9]{1}',
        'ES' => 'ES[0-9]{2}[0-9]{4}[0-9]{4}[0-9]{1}[0-9]{1}[0-9]{10}',
        'FI' => 'FI[0-9]{2}[0-9]{6}[0-9]{7}[0-9]{1}',
        'FO' => 'FO[0-9]{2}[0-9]{4}[0-9]{9}[0-9]{1}',
        'FR' => 'FR[0-9]{2}[0-9]{5}[0-9]{5}[A-Z0-9]{11}[0-9]{2}',
        'GB' => 'GB[0-9]{2}[A-Z]{4}[0-9]{6}[0-9]{8}',
        'GE' => 'GE[0-9]{2}[A-Z]{2}[0-9]{16}',
        'GI' => 'GI[0-9]{2}[A-Z]{4}[A-Z0-9]{15}',
        'GL' => 'GL[0-9]{2}[0-9]{4}[0-9]{9}[0-9]{1}',
        'GR' => 'GR[0-9]{2}[0-9]{3}[0-9]{4}[A-Z0-9]{16}',
        'GT' => 'GT[0-9]{2}[A-Z0-9]{4}[A-Z0-9]{20}',
        'HR' => 'HR[0-9]{2}[0-9]{7}[0-9]{10}',
        'HU' => 'HU[0-9]{2}[0-9]{3}[0-9]{4}[0-9]{1}[0-9]{15}[0-9]{1}',
        'IE' => 'IE[0-9]{2}[A-Z]{4}[0-9]{6}[0-9]{8}',
        'IL' => 'IL[0-9]{2}[0-9]{3}[0-9]{3}[0-9]{13}',
        'IS' => 'IS[0-9]{2}[0-9]{4}[0-9]{2}[0-9]{6}[0-9]{10}',
        'IT' => 'IT[0-9]{2}[A-Z]{1}[0-9]{5}[0-9]{5}[A-Z0-9]{12}',
        'KW' => 'KW[0-9]{2}[A-Z]{4}[0-9]{22}',
        'KZ' => 'KZ[0-9]{2}[0-9]{3}[A-Z0-9]{13}',
        'LB' => 'LB[0-9]{2}[0-9]{4}[A-Z0-9]{20}',
        'LI' => 'LI[0-9]{2}[0-9]{5}[A-Z0-9]{12}',
        'LT' => 'LT[0-9]{2}[0-9]{5}[0-9]{11}',
        'LU' => 'LU[0-9]{2}[0-9]{3}[A-Z0-9]{13}',
        'LV' => 'LV[0-9]{2}[A-Z]{4}[A-Z0-9]{13}',
        'MC' => 'MC[0-9]{2}[0-9]{5}[0-9]{5}[A-Z0-9]{11}[0-9]{2}',
        'MD' => 'MD[0-9]{2}[A-Z0-9]{20}',
        'ME' => 'ME[0-9]{2}[0-9]{3}[0-9]{13}[0-9]{2}',
        'MK' => 'MK[0-9]{2}[0-9]{3}[A-Z0-9]{10}[0-9]{2}',
        'MR' => 'MR13[0-9]{5}[0-9]{5}[0-9]{11}[0-9]{2}',
        'MT' => 'MT[0-9]{2}[A-Z]{4}[0-9]{5}[A-Z0-9]{18}',
        'MU' => 'MU[0-9]{2}[A-Z]{4}[0-9]{2}[0-9]{2}[0-9]{12}[0-9]{3}[A-Z]{3}',
        'NL' => 'NL[0-9]{2}[A-Z]{4}[0-9]{10}',
        'NO' => 'NO[0-9]{2}[0-9]{4}[0-9]{6}[0-9]{1}',
        'UA' => 'UA[0-9]{2}[0-9]{6}[0-9]{19}',
        'PK' => 'PK[0-9]{2}[A-Z]{4}[A-Z0-9]{16}',
        'PL' => 'PL[0-9]{2}[0-9]{8}[0-9]{16}',
        'PS' => 'PS[0-9]{2}[A-Z]{4}[A-Z0-9]{21}',
        'PT' => 'PT[0-9]{2}[0-9]{4}[0-9]{4}[0-9]{11}[0-9]{2}',
        'RO' => 'RO[0-9]{2}[A-Z]{4}[A-Z0-9]{16}',
        'RS' => 'RS[0-9]{2}[0-9]{3}[0-9]{13}[0-9]{2}',
        'SA' => 'SA[0-9]{2}[0-9]{2}[A-Z0-9]{18}',
        'SE' => 'SE[0-9]{2}[0-9]{3}[0-9]{16}[0-9]{1}',
        'SI' => 'SI[0-9]{2}[0-9]{5}[0-9]{8}[0-9]{2}',
        'SK' => 'SK[0-9]{2}[0-9]{4}[0-9]{6}[0-9]{10}',
        'SM' => 'SM[0-9]{2}[A-Z]{1}[0-9]{5}[0-9]{5}[A-Z0-9]{12}',
        'TN' => 'TN59[0-9]{2}[0-9]{3}[0-9]{13}[0-9]{2}',
        'TR' => 'TR[0-9]{2}[0-9]{5}[A-Z0-9]{1}[A-Z0-9]{16}',
        'VG' => 'VG[0-9]{2}[A-Z]{4}[0-9]{16}',
    ];

    /** @param OptionsArgument $options */
    public function __construct(array $options = [])
    {
        $countryCode = $options['country_code'] ?? null;
        if ($countryCode !== null && ! isset(self::IBAN_REGEX[$countryCode])) {
            throw new InvalidArgumentException(
                "Country code '{$countryCode}' invalid by ISO 3166-1 or not supported"
            );
        }

        $this->countryCode  = $countryCode;
        $this->allowNonSepa = $options['allow_non_sepa'] ?? true;

        unset($options['country_code'], $options['allow_non_sepa']);

        parent::__construct($options);
    }

    /**
     * Returns true if $value is a valid IBAN
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value)) {
            $this->error(self::FALSEFORMAT);
            return false;
        }

        $value = str_replace(' ', '', strtoupper($value));
        $this->setValue($value);

        $countryCode = $this->countryCode;
        if ($countryCode === null) {
            $countryCode = substr($value, 0, 2);
        }

        if (! array_key_exists($countryCode, self::IBAN_REGEX)) {
            $this->setValue($countryCode);
            $this->error(self::NOTSUPPORTED);
            return false;
        }

        if (! $this->allowNonSepa && ! in_array($countryCode, self::SEPA_COUNTRIES)) {
            $this->setValue($countryCode);
            $this->error(self::SEPANOTSUPPORTED);
            return false;
        }

        if (! preg_match('/^' . self::IBAN_REGEX[$countryCode] . '$/', $value)) {
            $this->error(self::FALSEFORMAT);
            return false;
        }

        $format = substr($value, 4) . substr($value, 0, 4);
        $format = str_replace(
            [
                'A',
                'B',
                'C',
                'D',
                'E',
                'F',
                'G',
                'H',
                'I',
                'J',
                'K',
                'L',
                'M',
                'N',
                'O',
                'P',
                'Q',
                'R',
                'S',
                'T',
                'U',
                'V',
                'W',
                'X',
                'Y',
                'Z',
            ],
            [
                '10',
                '11',
                '12',
                '13',
                '14',
                '15',
                '16',
                '17',
                '18',
                '19',
                '20',
                '21',
                '22',
                '23',
                '24',
                '25',
                '26',
                '27',
                '28',
                '29',
                '30',
                '31',
                '32',
                '33',
                '34',
                '35',
            ],
            $format
        );

        $temp = intval(substr($format, 0, 1));
        $len  = strlen($format);
        for ($x = 1; $x < $len; ++$x) {
            $temp *= 10;
            $temp += intval(substr($format, $x, 1));
            $temp %= 97;
        }

        if ($temp !== 1) {
            $this->error(self::CHECKFAILED);
            return false;
        }

        return true;
    }
}
