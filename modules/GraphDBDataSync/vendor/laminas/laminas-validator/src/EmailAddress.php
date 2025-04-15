<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;
use UConverter;

use function array_combine;
use function array_filter;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function arsort;
use function checkdnsrr;
use function gethostbynamel;
use function getmxrr;
use function idn_to_ascii;
use function is_array;
use function is_string;
use function preg_match;
use function str_contains;
use function strlen;
use function trim;

use const ARRAY_FILTER_USE_BOTH;
use const INTL_IDNA_VARIANT_UTS46;

/**
 * @psalm-type Options = array{
 *     useMxCheck?: bool,
 *     useDeepMxCheck?: bool,
 *     useDomainCheck?: bool,
 *     allow?: int-mask-of<Hostname::ALLOW_*>,
 *     strict?: bool,
 *     hostnameValidator?: Hostname|null,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class EmailAddress extends AbstractValidator
{
    public const INVALID            = 'emailAddressInvalid';
    public const INVALID_FORMAT     = 'emailAddressInvalidFormat';
    public const INVALID_HOSTNAME   = 'emailAddressInvalidHostname';
    public const INVALID_MX_RECORD  = 'emailAddressInvalidMxRecord';
    public const INVALID_SEGMENT    = 'emailAddressInvalidSegment';
    public const DOT_ATOM           = 'emailAddressDotAtom';
    public const QUOTED_STRING      = 'emailAddressQuotedString';
    public const INVALID_LOCAL_PART = 'emailAddressInvalidLocalPart';
    public const LENGTH_EXCEEDED    = 'emailAddressLengthExceeded';

    // phpcs:disable Generic.Files.LineLength.TooLong

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::INVALID            => "Invalid type given. String expected",
        self::INVALID_FORMAT     => "The input is not a valid email address. Use the basic format local-part@hostname",
        self::INVALID_HOSTNAME   => "'%hostname%' is not a valid hostname for the email address",
        self::INVALID_MX_RECORD  => "'%hostname%' does not appear to have any valid MX or A records for the email address",
        self::INVALID_SEGMENT    => "'%hostname%' is not in a routable network segment. The email address should not be resolved from public network",
        self::DOT_ATOM           => "'%localPart%' can not be matched against dot-atom format",
        self::QUOTED_STRING      => "'%localPart%' can not be matched against quoted-string format",
        self::INVALID_LOCAL_PART => "'%localPart%' is not a valid local part for the email address",
        self::LENGTH_EXCEEDED    => "The input exceeds the allowed length",
    ];

    // phpcs:enable

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'hostname'  => 'hostname',
        'localPart' => 'localPart',
    ];

    protected ?string $hostname  = null;
    protected ?string $localPart = null;

    private readonly Hostname $hostnameValidator;
    private readonly bool $useMxCheck;
    private readonly bool $useDeepMxCheck;
    private readonly bool $useDomainCheck;
    private readonly bool $strict;

    /**
     * Instantiates hostname validator for local use
     *
     * The following additional option keys are supported:
     * 'hostnameValidator' => A hostname validator, see Laminas\Validator\Hostname
     * 'allow'             => Options for the hostname validator, see Laminas\Validator\Hostname::ALLOW_*
     * 'strict'            => Whether to adhere to strictest requirements in the spec
     * 'useMxCheck'        => If MX check should be enabled, boolean
     * 'useDeepMxCheck'    => If a deep MX check should be done, boolean
     *
     * @param Options $options
     */
    public function __construct(array $options = [])
    {
        $messages         = $options['messages'] ?? [];
        $hostnameMessages = array_filter(
            $messages,
            fn (string $value, string $key): bool => ! array_key_exists($key, $this->messageTemplates),
            ARRAY_FILTER_USE_BOTH,
        );
        $messages         = array_filter(
            $messages,
            fn (string $value, string $key): bool => array_key_exists($key, $this->messageTemplates),
            ARRAY_FILTER_USE_BOTH,
        );

        $allow                   = $options['allow'] ?? Hostname::ALLOW_DNS;
        $this->hostnameValidator = $options['hostnameValidator'] ?? new Hostname([
            'allow'    => $allow,
            'messages' => $hostnameMessages,
        ]);
        $this->useMxCheck        = $options['useMxCheck'] ?? false;
        $this->useDeepMxCheck    = $options['useDeepMxCheck'] ?? false;
        $this->useDomainCheck    = $options['useDomainCheck'] ?? true;
        $this->strict            = $options['strict'] ?? true;

        unset(
            $options['allow'],
            $options['hostnameValidator'],
            $options['useMxCheck'],
            $options['useDeepMxCheck'],
            $options['useDomainCheck'],
            $options['strict'],
        );

        $options['messages'] = $messages;

        parent::__construct($options);
    }

    /**
     * Overrides `setMessage` of AbstractValidator so that messages propagate to the composed hostname validator
     *
     * @inheritDoc
     */
    public function setMessage(string $messageString, ?string $messageKey = null): void
    {
        if ($messageKey === null) {
            $this->hostnameValidator->setMessage($messageString);
            parent::setMessage($messageString);
        }

        if (! isset($this->messageTemplates[$messageKey])) {
            $this->hostnameValidator->setMessage($messageString, $messageKey);
        } else {
            parent::setMessage($messageString, $messageKey);
        }
    }

    /**
     * Returns whether the given host is a reserved IP, or a hostname that resolves to a reserved IP
     */
    private function isReserved(string $host): bool
    {
        $validator = new HostWithPublicIPv4Address();
        return ! $validator->isValid($host);
    }

    /**
     * Internal method to validate the local part of the email address
     */
    private function validateLocalPart(string $localPart): bool
    {
        // First try to match the local part on the common dot-atom format

        // Dot-atom characters are: 1*atext *("." 1*atext)
        // atext: ALPHA / DIGIT / and "!", "#", "$", "%", "&", "'", "*",
        //        "+", "-", "/", "=", "?", "^", "_", "`", "{", "|", "}", "~"
        $atext = 'a-zA-Z0-9\x21\x23\x24\x25\x26\x27\x2a\x2b\x2d\x2f\x3d\x3f\x5e\x5f\x60\x7b\x7c\x7d\x7e';
        if (preg_match('/^[' . $atext . ']+(\x2e+[' . $atext . ']+)*$/', $localPart)) {
            return true;
        }

        if ($this->validateInternationalizedLocalPart($localPart)) {
            return true;
        }

        // Try quoted string format (RFC 5321 Chapter 4.1.2)

        // Quoted-string characters are: DQUOTE *(qtext/quoted-pair) DQUOTE
        $qtext      = '\x20-\x21\x23-\x5b\x5d-\x7e'; // %d32-33 / %d35-91 / %d93-126
        $quotedPair = '\x20-\x7e'; // %d92 %d32-126
        if (preg_match('/^"([' . $qtext . ']|\x5c[' . $quotedPair . '])*"$/', $localPart)) {
            return true;
        }

        $this->error(self::DOT_ATOM);
        $this->error(self::QUOTED_STRING);
        $this->error(self::INVALID_LOCAL_PART);

        return false;
    }

    /**
     * @param string $localPart Address local part to validate.
     */
    protected function validateInternationalizedLocalPart(string $localPart): bool
    {
        if (UConverter::transcode($localPart, 'UTF-8', 'UTF-8') === false) {
            // invalid utf?
            return false;
        }

        $atext = 'a-zA-Z0-9\x21\x23\x24\x25\x26\x27\x2a\x2b\x2d\x2f\x3d\x3f\x5e\x5f\x60\x7b\x7c\x7d\x7e';
        // RFC 6532 extends atext to include non-ascii utf
        // @see https://tools.ietf.org/html/rfc6532#section-3.1
        $uatext = $atext . '\x{80}-\x{FFFF}';
        return (bool) preg_match('/^[' . $uatext . ']+(\x2e+[' . $uatext . ']+)*$/u', $localPart);
    }

    /**
     * Internal method to validate the servers MX records
     */
    protected function validateMXRecords(string $hostname): bool
    {
        $mxHosts  = [];
        $weight   = [];
        $mxRecord = [];
        $result   = getmxrr($hostname, $mxHosts, $weight);

        if ($result) {
            $mxRecord = array_combine($mxHosts, $weight) ?: [];
            arsort($mxRecord);
        }

        // Fallback to IPv4 hosts if no MX record found (RFC 2821 SS 5).
        if (! $result) {
            $result = gethostbynamel($hostname);
            if (is_array($result)) {
                $mxRecord = array_flip($result);
            }
        }

        if ($result === false) {
            $this->error(self::INVALID_MX_RECORD);
            return false;
        }

        if (! $this->useDeepMxCheck) {
            return true;
        }

        $validAddress = false;
        $reserved     = true;
        foreach (array_keys($mxRecord) as $mxHost) {
            $res = $this->isReserved($mxHost);
            if (! $res) {
                $reserved = false;
            }

            if (trim($mxHost) === '') {
                continue;
            }

            if (
                ! $res
                && (checkdnsrr($mxHost, 'A')
                || checkdnsrr($mxHost, 'AAAA')
                || checkdnsrr($mxHost, 'A6'))
            ) {
                $validAddress = true;
                break;
            }
        }

        if (! $validAddress) {
            $error = $reserved ? self::INVALID_SEGMENT : self::INVALID_MX_RECORD;
            $this->error($error);

            return false;
        }

        return true;
    }

    /**
     * Internal method to validate the hostname part of the email address
     */
    private function validateHostnamePart(string $hostname): bool
    {
        $this->hostnameValidator->setTranslator($this->getTranslator());
        $isValid = $this->hostnameValidator->isValid($hostname);
        if (! $isValid) {
            $this->error(self::INVALID_HOSTNAME);
            // Get messages and errors from hostnameValidator
            foreach ($this->hostnameValidator->getMessages() as $code => $message) {
                $this->errorMessages[$code] = $message;
            }

            return false;
        } elseif ($this->useMxCheck) {
            // MX check on hostname
            $isValid = $this->validateMXRecords($hostname);
        }

        return $isValid;
    }

    /**
     * Splits the given value in hostname and local part of the email address
     *
     * @return array{localPart: string, hostname: string}|false Returns false when the email can not be split
     */
    private static function splitEmailParts(string $value): array|false
    {
        // Split email address up and disallow '..'
        if (
            str_contains($value, '..')
            || ! preg_match('/^(.+)@([^@]+)$/', $value, $matches)
        ) {
            return false;
        }

        return [
            'localPart' => $matches[1],
            'hostname'  => self::idnToAscii($matches[2]),
        ];
    }

    /**
     * Defined by Laminas\Validator\ValidatorInterface
     *
     * Returns true if and only if $value is a valid email address
     * according to RFC2822
     *
     * @link   http://www.ietf.org/rfc/rfc2822.txt RFC2822
     * @link   http://www.columbia.edu/kermit/ascii.html US-ASCII characters
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $length = true;
        $this->setValue($value);

        // Split email address up and disallow '..'
        $split = self::splitEmailParts($value);
        if ($split === false) {
            $this->error(self::INVALID_FORMAT);
            return false;
        }

        ['localPart' => $localPart, 'hostname' => $hostname] = $split;

        $this->localPart = $localPart;
        $this->hostname  = $hostname;

        if ($this->strict && (strlen($localPart) > 64) || (strlen($hostname) > 255)) {
            $length = false;
            $this->error(self::LENGTH_EXCEEDED);
        }

        // Match hostname part
        $hostnameValid = false;
        if ($this->useDomainCheck) {
            $hostnameValid = $this->validateHostnamePart($hostname);
        }

        $local = $this->validateLocalPart($localPart);

        // If both parts valid, return true
        return ($local && $length) && (! $this->useDomainCheck || $hostnameValid !== false);
    }

    /**
     * Safely convert UTF-8 encoded domain name to ASCII
     *
     * @param string $hostname the UTF-8 encoded email
     */
    private static function idnToAscii(string $hostname): string
    {
        $value = idn_to_ascii($hostname, 0, INTL_IDNA_VARIANT_UTS46);

        return $value !== false ? $value : $hostname;
    }
}
