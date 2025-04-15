<?php

declare(strict_types=1);

namespace Laminas\Validator;

use function assert;
use function explode;
use function filter_var;
use function get_debug_type;
use function gethostbynamel;
use function ip2long;
use function is_array;
use function is_int;
use function is_string;

use const FILTER_FLAG_GLOBAL_RANGE;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_VALIDATE_IP;
use const PHP_VERSION_ID;

final class HostWithPublicIPv4Address extends AbstractValidator
{
    /**
     * Reserved CIDRs are extracted from IANA with additions from Wikipedia
     *
     * @link https://www.iana.org/assignments/iana-ipv4-special-registry/iana-ipv4-special-registry.xhtml
     * @link https://en.wikipedia.org/wiki/Reserved_IP_addresses
     */
    private const RESERVED_CIDR = [
        '0.0.0.0/8',
        '0.0.0.0/32',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.0.0/29',
        '192.0.0.8/32',
        '192.0.0.9/32',
        '192.0.0.10/32',
        '192.0.0.170/32',
        '192.0.0.171/32',
        '192.0.2.0/24',
        '192.31.196.0/24',
        '192.52.193.0/24',
        '192.88.99.0/24',
        '192.168.0.0/16',
        '192.175.48.0/24',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4', // Wikipedia
        '233.252.0.0/24', // Wikipedia
        '240.0.0.0/4',
        '255.255.255.255/32',
    ];

    public const ERROR_NOT_STRING            = 'hostnameNotString';
    public const ERROR_HOSTNAME_NOT_RESOLVED = 'hostnameNotResolved';
    public const ERROR_PRIVATE_IP_FOUND      = 'privateIpAddressFound';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::ERROR_NOT_STRING            => 'Expected a string hostname but received %type%',
        self::ERROR_HOSTNAME_NOT_RESOLVED => 'The hostname "%value%" cannot be resolved',
        self::ERROR_PRIVATE_IP_FOUND      => 'The hostname "%value%" resolves to at least one reserved IPv4 address',
    ];

    protected string $type = 'null';

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'type'  => 'type',
        'value' => 'value',
    ];

    public function isValid(mixed $value): bool
    {
        $this->type = get_debug_type($value);

        if (! is_string($value)) {
            $this->error(self::ERROR_NOT_STRING);

            return false;
        }

        $this->setValue($value);

        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            $addressList = gethostbynamel($value);
        } else {
            $addressList = [$value];
        }

        if (! is_array($addressList)) {
            $this->error(self::ERROR_HOSTNAME_NOT_RESOLVED);

            return false;
        }

        $privateAddressWasFound = false;

        $filterFlags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (PHP_VERSION_ID >= 80200) {
            /**
             * @psalm-var int $filterFlags
             * @psalm-suppress UndefinedConstant,MixedAssignment This trips up Psalm quite badly
             */
            $filterFlags |= FILTER_FLAG_GLOBAL_RANGE;
        }

        assert(is_int($filterFlags));

        foreach ($addressList as $server) {
            /**
             * Initially test with PHP's built-in filter_var features as this will be quicker than checking
             * presence with a CIDR
             */
            if (filter_var($server, FILTER_VALIDATE_IP, $filterFlags) === false) {
                $privateAddressWasFound = true;

                break;
            }

            if ($this->inReservedCidr($server)) {
                $privateAddressWasFound = true;

                break;
            }
        }

        if ($privateAddressWasFound) {
            $this->error(self::ERROR_PRIVATE_IP_FOUND);

            return false;
        }

        return true;
    }

    private function inReservedCidr(string $ip): bool
    {
        foreach (self::RESERVED_CIDR as $cidr) {
            $cidr    = explode('/', $cidr);
            $startIp = ip2long($cidr[0]);
            assert(is_int($startIp));
            $endIp = $startIp + 2 ** (32 - (int) $cidr[1]) - 1;
            assert(is_int($endIp));

            $int = ip2long($ip);

            if ($int >= $startIp && $int <= $endIp) {
                return true;
            }
        }

        return false;
    }
}
