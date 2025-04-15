<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Barcode\AdapterInterface;
use Laminas\Validator\Barcode\Ean13;
use Laminas\Validator\Exception\InvalidArgumentException;

use function class_exists;
use function implode;
use function is_a;
use function is_array;
use function is_string;
use function sprintf;
use function strtolower;
use function ucfirst;

/**
 * @psalm-type OptionsArgument = array{
 *     adapter?: AdapterInterface|class-string<AdapterInterface>|string,
 *     useChecksum?: bool,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 * @psalm-import-type AllowedLength from AdapterInterface
 */
final class Barcode extends AbstractValidator
{
    public const INVALID        = 'barcodeInvalid';
    public const FAILED         = 'barcodeFailed';
    public const INVALID_CHARS  = 'barcodeInvalidChars';
    public const INVALID_LENGTH = 'barcodeInvalidLength';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::FAILED         => 'The input failed checksum validation',
        self::INVALID_CHARS  => 'The input contains invalid characters',
        self::INVALID_LENGTH => 'The input should have a length of %length% characters',
        self::INVALID        => 'Invalid type given. String expected',
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'length' => 'length',
    ];

    private readonly AdapterInterface $adapter;
    protected readonly string $length;
    private readonly bool $useChecksum;

    /** @param OptionsArgument $options */
    public function __construct(array $options = [])
    {
        $this->adapter     = $this->resolveAdapter($options['adapter'] ?? new Ean13());
        $this->length      = $this->stringifyExpectedLength();
        $this->useChecksum = $options['useChecksum'] ?? false;

        parent::__construct($options);
    }

    private function resolveAdapter(string|AdapterInterface $adapter): AdapterInterface
    {
        if (is_string($adapter) && ! class_exists($adapter)) {
            $adapter = sprintf('Laminas\\Validator\\Barcode\\%s', ucfirst(strtolower($adapter)));
        }

        if (is_string($adapter) && is_a($adapter, AdapterInterface::class, true)) {
            $adapter = new $adapter();
        }

        if (! $adapter instanceof AdapterInterface) {
            throw new InvalidArgumentException(sprintf(
                'The "adapter" option must resolve to an instance of %s',
                AdapterInterface::class,
            ));
        }

        return $adapter;
    }

    private function stringifyExpectedLength(): string
    {
        $length = $this->adapter->getLength();
        if (is_array($length)) {
            $length = implode('/', $length);
        }

        return (string) $length;
    }

    /**
     * Defined by Laminas\Validator\ValidatorInterface
     *
     * Returns true if and only if $value contains a valid barcode
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);

        if (! $this->adapter->hasValidLength($value)) {
            $this->error(self::INVALID_LENGTH);
            return false;
        }

        if (! $this->adapter->hasValidCharacters($value)) {
            $this->error(self::INVALID_CHARS);
            return false;
        }

        if ($this->useChecksum && ! $this->adapter->hasValidChecksum($value)) {
            $this->error(self::FAILED);
            return false;
        }

        return true;
    }
}
