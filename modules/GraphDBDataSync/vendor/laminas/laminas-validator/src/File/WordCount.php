<?php

declare(strict_types=1);

namespace Laminas\Validator\File;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception\InvalidArgumentException;

use function assert;
use function file_get_contents;
use function is_string;
use function str_word_count;

/**
 * Validator for counting all words in a file
 *
 * @psalm-type OptionsArgument = array{
 *     min?: numeric,
 *     max?: numeric,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class WordCount extends AbstractValidator
{
    /**
     * @const string Error constants
     */
    public const TOO_MUCH  = 'fileWordCountTooMuch';
    public const TOO_LESS  = 'fileWordCountTooLess';
    public const NOT_FOUND = 'fileWordCountNotFound';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::TOO_MUCH  => "Too many words, maximum '%max%' are allowed but '%count%' were counted",
        self::TOO_LESS  => "Too few words, minimum '%min%' are expected but '%count%' were counted",
        self::NOT_FOUND => 'File is not readable or does not exist',
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'min'   => 'min',
        'max'   => 'max',
        'count' => 'count',
    ];

    /**
     * Word count
     */
    protected ?int $count = null;

    protected readonly int|null $min;
    protected readonly int|null $max;

    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $min = $options['min'] ?? null;
        $max = $options['max'] ?? null;

        if ($min === null && $max === null) {
            throw new InvalidArgumentException('A minimum or maximum word count must be set');
        }

        $min = $min !== null ? (int) $min : null;
        $max = $max !== null ? (int) $max : null;

        if ($min !== null && $max !== null && $min > $max) {
            throw new InvalidArgumentException('The minimum word count should be less than the maximum word count');
        }

        unset($options['min'], $options['max']);

        $this->min = $min;
        $this->max = $max;

        parent::__construct($options);
    }

    /**
     * Returns true if and only if the counted words are at least min and
     * not bigger than max (when max is not null).
     */
    public function isValid(mixed $value): bool
    {
        if (! FileInformation::isPossibleFile($value)) {
            $this->error(self::NOT_FOUND);

            return false;
        }

        $file = FileInformation::factory($value);

        if (! $file->readable) {
            $this->error(self::NOT_FOUND);

            return false;
        }

        $content = file_get_contents($file->path);
        assert(is_string($content));
        $this->count = str_word_count($content);

        if (($this->max !== null) && ($this->count > $this->max)) {
            $this->error(self::TOO_MUCH);
            return false;
        }

        if (($this->min !== null) && ($this->count < $this->min)) {
            $this->error(self::TOO_LESS);
            return false;
        }

        return true;
    }
}
