<?php

declare(strict_types=1);

namespace Laminas\Validator\File;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception\InvalidArgumentException;

use function is_array;

/**
 * Validator for counting all given files
 *
 * @psalm-type OptionsArgument = array{
 *     min?: positive-int|null,
 *     max?: positive-int|null,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Count extends AbstractValidator
{
    public const TOO_MANY        = 'fileCountTooMany';
    public const TOO_FEW         = 'fileCountTooFew';
    public const ERROR_NOT_ARRAY = 'fileListNotCountable';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::TOO_MANY        => "Too many files, maximum '%max%' are allowed but '%count%' are given",
        self::TOO_FEW         => "Too few files, minimum '%min%' are expected but '%count%' are given",
        self::ERROR_NOT_ARRAY => 'Invalid type provided. The file list must an array.',
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'min'   => 'min',
        'max'   => 'max',
        'count' => 'count',
    ];

    protected int $count;
    protected readonly int|null $min;
    protected readonly int|null $max;

    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $min = $options['min'] ?? 0;
        $max = $options['max'] ?? null;

        if ($max !== null && $min > $max) {
            throw new InvalidArgumentException(
                'The `min` option cannot exceed the `max` option',
            );
        }

        $this->count = 0;
        $this->min   = $min;
        $this->max   = $max;

        unset($options['min'], $options['max']);

        parent::__construct($options);
    }

    /**
     * Returns true if and only if the file count of all checked files is at least min and
     * not bigger than max (when max is not null).
     */
    public function isValid(mixed $value): bool
    {
        if (FileInformation::isPossibleFile($value)) {
            $value = [$value];
        }

        if (! is_array($value)) {
            $this->error(self::ERROR_NOT_ARRAY);

            return false;
        }

        $this->count = 0;
        /** @psalm-var mixed $item */
        foreach ($value as $item) {
            if (! FileInformation::isPossibleFile($item)) {
                continue;
            }

            $this->count++;
        }

        if ($this->min !== null && $this->count < $this->min) {
            $this->error(self::TOO_FEW);

            return false;
        }

        if ($this->max !== null && $this->count > $this->max) {
            $this->error(self::TOO_MANY);

            return false;
        }

        return true;
    }
}
