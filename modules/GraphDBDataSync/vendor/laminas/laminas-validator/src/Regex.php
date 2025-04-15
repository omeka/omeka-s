<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;

use function is_string;
use function preg_match;

/**
 * @psalm-type OptionsArgument = array{
 *     pattern: non-empty-string,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Regex extends AbstractValidator
{
    public const INVALID   = 'regexInvalid';
    public const NOT_MATCH = 'regexNotMatch';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::INVALID   => 'Invalid type given. String, integer or float expected',
        self::NOT_MATCH => "The input does not match against pattern '%pattern%'",
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'pattern' => 'pattern',
    ];

    /**
     * Regular expression pattern
     *
     * @var non-empty-string
     */
    protected string $pattern;

    /**
     * Sets validator options
     *
     * @param non-empty-string|OptionsArgument $options
     */
    public function __construct(string|array $options)
    {
        $options = is_string($options) ? ['pattern' => $options] : $options;
        $pattern = $options['pattern'] ?? null;
        /** @psalm-suppress DocblockTypeContradiction The user may still supply an empty string */
        if (! is_string($pattern) || $pattern === '') {
            throw new InvalidArgumentException('A regex pattern is required');
        }

        $status = preg_match($pattern, 'Test');
        if ($status === false) {
            throw new InvalidArgumentException(
                "Internal error parsing the pattern '{$pattern}'",
            );
        }

        $this->pattern = $pattern;

        parent::__construct($options);
    }

    /**
     * Returns true if and only if $value matches against the pattern option
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);

        $status = preg_match($this->pattern, $value);

        if ((bool) $status === false) {
            $this->error(self::NOT_MATCH);
            return false;
        }

        return true;
    }
}
