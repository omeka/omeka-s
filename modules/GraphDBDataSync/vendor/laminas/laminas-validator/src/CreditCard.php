<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;
use SensitiveParameter;
use Throwable;

use function constant;
use function ctype_digit;
use function defined;
use function floor;
use function in_array;
use function is_callable;
use function is_string;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strtoupper;

/**
 * @psalm-type OptionsArgument = array{
 *     type?: value-of<CreditCard::TYPES>|list<value-of<CreditCard::TYPES>>,
 *     service?: callable(mixed, array<string, mixed>, mixed...): bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class CreditCard extends AbstractValidator
{
    /**
     * Detected CCI list
     */
    public const ALL              = 'All';
    public const AMERICAN_EXPRESS = 'American_Express';
    public const UNIONPAY         = 'Unionpay';
    public const DINERS_CLUB      = 'Diners_Club';
    public const DINERS_CLUB_US   = 'Diners_Club_US';
    public const DISCOVER         = 'Discover';
    public const JCB              = 'JCB';
    public const LASER            = 'Laser';
    public const MAESTRO          = 'Maestro';
    public const MASTERCARD       = 'Mastercard';
    public const SOLO             = 'Solo';
    public const VISA             = 'Visa';
    public const MIR              = 'Mir';

    public const CHECKSUM       = 'creditcardChecksum';
    public const CONTENT        = 'creditcardContent';
    public const INVALID        = 'creditcardInvalid';
    public const LENGTH         = 'creditcardLength';
    public const PREFIX         = 'creditcardPrefix';
    public const SERVICE        = 'creditcardService';
    public const SERVICEFAILURE = 'creditcardServiceFailure';

    private const TYPES = [
        self::AMERICAN_EXPRESS,
        self::DINERS_CLUB,
        self::DINERS_CLUB_US,
        self::DISCOVER,
        self::JCB,
        self::LASER,
        self::MAESTRO,
        self::MASTERCARD,
        self::SOLO,
        self::UNIONPAY,
        self::VISA,
        self::MIR,
    ];

    private const CARD_LENGTH = [
        self::AMERICAN_EXPRESS => [15],
        self::DINERS_CLUB      => [14],
        self::DINERS_CLUB_US   => [16],
        self::DISCOVER         => [16, 19],
        self::JCB              => [15, 16],
        self::LASER            => [16, 17, 18, 19],
        self::MAESTRO          => [12, 13, 14, 15, 16, 17, 18, 19],
        self::MASTERCARD       => [16],
        self::SOLO             => [16, 18, 19],
        self::UNIONPAY         => [16, 17, 18, 19],
        self::VISA             => [13, 16, 19],
        self::MIR              => [13, 16],
    ];

    private const CARD_PREFIXES = [
        self::AMERICAN_EXPRESS => ['34', '37'],
        self::DINERS_CLUB      => ['300', '301', '302', '303', '304', '305', '36'],
        self::DINERS_CLUB_US   => ['54', '55'],
        self::DISCOVER         => [
            '6011',
            '622126',
            '622127',
            '622128',
            '622129',
            '62213',
            '62214',
            '62215',
            '62216',
            '62217',
            '62218',
            '62219',
            '6222',
            '6223',
            '6224',
            '6225',
            '6226',
            '6227',
            '6228',
            '62290',
            '62291',
            '622920',
            '622921',
            '622922',
            '622923',
            '622924',
            '622925',
            '644',
            '645',
            '646',
            '647',
            '648',
            '649',
            '65',
        ],
        self::JCB              => ['1800', '2131', '3528', '3529', '353', '354', '355', '356', '357', '358'],
        self::LASER            => ['6304', '6706', '6771', '6709'],
        self::MAESTRO          => [
            '5018',
            '5020',
            '5038',
            '6304',
            '6759',
            '6761',
            '6762',
            '6763',
            '6764',
            '6765',
            '6766',
            '6772',
        ],
        self::MASTERCARD       => [
            '2221',
            '2222',
            '2223',
            '2224',
            '2225',
            '2226',
            '2227',
            '2228',
            '2229',
            '223',
            '224',
            '225',
            '226',
            '227',
            '228',
            '229',
            '23',
            '24',
            '25',
            '26',
            '271',
            '2720',
            '51',
            '52',
            '53',
            '54',
            '55',
        ],
        self::SOLO             => ['6334', '6767'],
        self::UNIONPAY         => [
            '622126',
            '622127',
            '622128',
            '622129',
            '62213',
            '62214',
            '62215',
            '62216',
            '62217',
            '62218',
            '62219',
            '6222',
            '6223',
            '6224',
            '6225',
            '6226',
            '6227',
            '6228',
            '62290',
            '62291',
            '622920',
            '622921',
            '622922',
            '622923',
            '622924',
            '622925',
        ],
        self::VISA             => ['4'],
        self::MIR              => ['2200', '2201', '2202', '2203', '2204'],
    ];

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::CHECKSUM       => 'The input seems to contain an invalid checksum',
        self::CONTENT        => 'The input must contain only digits',
        self::INVALID        => 'Invalid type given. String expected',
        self::LENGTH         => 'The input contains an invalid amount of digits',
        self::PREFIX         => 'The input is not from an allowed institute',
        self::SERVICE        => 'The input seems to be an invalid credit card number',
        self::SERVICEFAILURE => 'An exception has been raised while validating the input',
    ];

    /** @var list<value-of<self::TYPES>> */
    private readonly array $type;
    private readonly ?Callback $callback;

    /**
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $type       = $options['type'] ?? self::ALL;
        $this->type = $this->resolveType($type);

        $service = $options['service'] ?? null;

        if ($service !== null && ! is_callable($service)) {
            throw new InvalidArgumentException('Invalid callback given');
        }

        if (is_callable($service)) {
            $this->callback = new Callback([
                'callback'        => $service,
                'callbackOptions' => [$this->type],
                'throwExceptions' => true,
            ]);
        } else {
            $this->callback = null;
        }

        unset($options['type'], $options['service']);

        parent::__construct($options);
    }

    /**
     * @param string|array<array-key, string> $types
     * @return list<value-of<self::TYPES>>
     */
    private function resolveType(string|array $types): array
    {
        if (is_string($types)) {
            $types = [$types];
        }

        $list = [];
        foreach ($types as $type) {
            if ($type === self::ALL) {
                return self::TYPES;
            }

            $value = $this->isValidType($type);
            if ($value !== null) {
                $list[] = $value;
                continue;
            }

            $constant = sprintf('static::%s', strtoupper($type));
            if (defined($constant)) {
                $value = $this->isValidType(constant($constant));
                if ($value !== null) {
                    $list[] = $value;
                }
            }
        }

        /** @psalm-var list<value-of<self::TYPES>> */

        return $list;
    }

    /**
     * @return value-of<self::TYPES>|null
     */
    private function isValidType(mixed $type): ?string
    {
        return is_string($type) && in_array($type, self::TYPES, true)
            ? $type
            : null;
    }

    /**
     * Returns true if and only if $value follows the Luhn algorithm (mod-10 checksum)
     *
     * @param array<string, mixed>|null $context Validation context, i.e the form payload
     */
    public function isValid(
        #[SensitiveParameter]
        mixed $value,
        ?array $context = null,
    ): bool {
        $this->setValue($value);

        if (! is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        if (! ctype_digit($value)) {
            $this->error(self::CONTENT);
            return false;
        }

        $length      = strlen($value);
        $prefixFound = false;
        $lengthFound = false;
        foreach ($this->type as $type) {
            foreach (self::CARD_PREFIXES[$type] as $prefix) {
                if (str_starts_with($value, $prefix)) {
                    $prefixFound = true;
                    if (in_array($length, self::CARD_LENGTH[$type])) {
                        $lengthFound = true;
                        break 2;
                    }
                }
            }
        }

        if ($prefixFound === false) {
            $this->error(self::PREFIX);
            return false;
        }

        if ($lengthFound === false) {
            $this->error(self::LENGTH);
            return false;
        }

        $sum    = 0;
        $weight = 2;

        for ($i = $length - 2; $i >= 0; $i--) {
            $digit  = $weight * (int) $value[$i];
            $sum   += floor($digit / 10) + $digit % 10;
            $weight = $weight % 2 + 1;
        }

        $checksum = (10 - $sum % 10) % 10;
        if ((string) $checksum !== $value[$length - 1]) {
            $this->error(self::CHECKSUM, $value);
            return false;
        }

        if ($this->callback !== null) {
            try {
                if (! $this->callback->isValid($value, $context)) {
                    $this->error(self::SERVICE);
                    return false;
                }
            } catch (Throwable) {
                $this->error(self::SERVICEFAILURE);
                return false;
            }
        }

        return true;
    }
}
