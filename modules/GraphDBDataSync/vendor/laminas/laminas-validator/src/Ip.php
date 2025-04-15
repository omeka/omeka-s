<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;

use function bindec;
use function hexdec;
use function ip2long;
use function is_string;
use function long2ip;
use function preg_match;
use function sprintf;
use function str_contains;
use function strlen;
use function strrpos;
use function substr;
use function substr_count;

/**
 * @psalm-type OptionsArgument = array{
 *     allowipv4?: bool,
 *     allowipv6?: bool,
 *     allowipvfuture?: bool,
 *     allowliteral?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Ip extends AbstractValidator
{
    public const INVALID        = 'ipInvalid';
    public const NOT_IP_ADDRESS = 'notIpAddress';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::INVALID        => 'Invalid type given. String expected',
        self::NOT_IP_ADDRESS => 'The input does not appear to be a valid IP address',
    ];

    private readonly bool $allowipv4;      // Enable IPv4 Validation
    private readonly bool $allowipv6;      // Enable IPv6 Validation
    private readonly bool $allowipvfuture; // Enable IPvFuture Validation
    private readonly bool $allowliteral;   // Enable IPs in literal format (only IPv6 and IPvFuture)

    /** @param OptionsArgument $options */
    public function __construct(array $options = [])
    {
        $this->allowipv4      = $options['allowipv4'] ?? true;
        $this->allowipv6      = $options['allowipv6'] ?? true;
        $this->allowipvfuture = $options['allowipvfuture'] ?? false;
        $this->allowliteral   = $options['allowliteral'] ?? true;

        if (
            $this->allowipv4 === false
            && $this->allowipv6 === false
            && $this->allowipvfuture === false
        ) {
            throw new Exception\InvalidArgumentException('Nothing to validate. Check your options');
        }

        unset(
            $options['allowipv4'],
            $options['allowipv6'],
            $options['allowipvfuture'],
            $options['allowliteral'],
        );

        parent::__construct($options);
    }

    /**
     * Returns true if and only if $value is a valid IP address
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value) || $value === '') {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);

        if ($this->allowipv4 && $this->validateIPv4($value)) {
            return true;
        } else {
            if ($this->allowliteral) {
                if (preg_match('/^\[(.*)\]$/', $value, $matches)) {
                    $value = $matches[1];
                }
            }

            if (
                ($this->allowipv6 && $this->validateIPv6($value)) ||
                ($this->allowipvfuture && $this->validateIPvFuture($value))
            ) {
                return true;
            }
        }

        $this->error(self::NOT_IP_ADDRESS);
        return false;
    }

    /**
     * Validates an IPv4 address
     */
    private function validateIPv4(string $value): bool
    {
        if (preg_match('/^([01]{8}\.){3}[01]{8}\z/i', $value)) {
            // binary format  00000000.00000000.00000000.00000000
            $value = sprintf(
                '%s.%s.%s.%s',
                bindec(substr($value, 0, 8)),
                bindec(substr($value, 9, 8)),
                bindec(substr($value, 18, 8)),
                bindec(substr($value, 27, 8)),
            );
        } elseif (preg_match('/^([0-9]{3}\.){3}[0-9]{3}\z/i', $value)) {
            // octet format 777.777.777.777
            $value = sprintf(
                '%d.%d.%d.%d',
                substr($value, 0, 3),
                substr($value, 4, 3),
                substr($value, 8, 3),
                substr($value, 12, 3),
            );
        } elseif (preg_match('/^([0-9a-f]{2}\.){3}[0-9a-f]{2}\z/i', $value)) {
            // hex format ff.ff.ff.ff
            $value = sprintf(
                '%s.%s.%s.%s',
                hexdec(substr($value, 0, 2)),
                hexdec(substr($value, 3, 2)),
                hexdec(substr($value, 6, 2)),
                hexdec(substr($value, 9, 2)),
            );
        }

        $ip2long = ip2long($value);
        if ($ip2long === false) {
            return false;
        }

        return $value === long2ip($ip2long);
    }

    /**
     * Validates an IPv6 address
     */
    private function validateIPv6(string $value): bool
    {
        if (strlen($value) < 3) {
            return $value === '::';
        }

        if (str_contains($value, '.')) {
            $lastcolon = strrpos($value, ':');
            if (! ($lastcolon !== false && $this->validateIPv4(substr($value, $lastcolon + 1)))) {
                return false;
            }

            $value = substr($value, 0, $lastcolon) . ':0:0';
        }

        if (! str_contains($value, '::')) {
            return (bool) preg_match('/\A(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}\z/i', $value);
        }

        $colonCount = substr_count($value, ':');
        if ($colonCount < 8) {
            return (bool) preg_match('/\A(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?\z/i', $value);
        }

        // special case with ending or starting double colon
        if ($colonCount === 8) {
            return (bool) preg_match('/\A(?:::)?(?:[a-f0-9]{1,4}:){6}[a-f0-9]{1,4}(?:::)?\z/i', $value);
        }

        return false;
    }

    /**
     * Validates an IPvFuture address.
     *
     * IPvFuture is loosely defined in the Section 3.2.2 of RFC 3986
     */
    private function validateIPvFuture(string $value): bool
    {
        /*
         * ABNF:
         * IPvFuture  = "v" 1*HEXDIG "." 1*( unreserved / sub-delims / ":" )
         * unreserved    = ALPHA / DIGIT / "-" / "." / "_" / "~"
         * sub-delims    = "!" / "$" / "&" / "'" / "(" / ")" / "*" / "+" / ","
         *               / ";" / "="
         */
        $regex = '/^v([[:xdigit:]]+)\.[[:alnum:]\-\._~!\$&\'\(\)\*\+,;=:]+$/';

        $result = (bool) preg_match($regex, $value, $matches);

        /*
         * "As such, implementations must not provide the version flag for the
         *  existing IPv4 and IPv6 literal address forms described below."
         */
        return $result && $matches[1] !== '4' && $matches[1] !== '6';
    }
}
