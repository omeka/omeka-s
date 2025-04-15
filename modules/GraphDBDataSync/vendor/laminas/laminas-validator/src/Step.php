<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Laminas\Translator\TranslatorInterface;

use function floor;
use function is_numeric;
use function round;
use function strlen;
use function strpos;
use function substr;

/**
 * @psalm-type OptionsArgument = array{
 *     baseValue?: numeric,
 *     step?: numeric,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Step extends AbstractValidator
{
    public const INVALID  = 'typeInvalid';
    public const NOT_STEP = 'stepInvalid';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::INVALID  => 'Invalid value given. Scalar expected',
        self::NOT_STEP => 'The input is not a valid step',
    ];

    private readonly float $baseValue;
    private readonly float $step;

    /**
     * Set default options for this instance
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        $base = $options['baseValue'] ?? null;
        $step = $options['step'] ?? null;

        $this->baseValue = is_numeric($base)
            ? (float) $base
            : 0.0;

        $this->step = is_numeric($step)
            ? (float) $step
            : 1.0;

        unset($options['baseValue'], $options['step']);

        parent::__construct($options);
    }

    /**
     * Returns true if $value is numeric and a valid step value
     */
    public function isValid(mixed $value): bool
    {
        if (! is_numeric($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);

        $subtract = $this->sub((float) $value, $this->baseValue);

        $fmod = $this->fmod($subtract, $this->step);

        if ($fmod !== 0.0 && $fmod !== $this->step) {
            $this->error(self::NOT_STEP);
            return false;
        }

        return true;
    }

    /**
     * replaces the internal fmod function which give wrong results on many cases
     */
    private function fmod(float $x, float $y): float
    {
        if ($y === 0.0) {
            return 1.0;
        }

        // find the maximum precision from both input params to give accurate results
        $precision = $this->getPrecision($x) + $this->getPrecision($y);

        return round($x - $y * floor($x / $y), $precision);
    }

    /**
     * replaces the internal subtraction operation which give wrong results on some cases
     */
    private function sub(float $x, float $y): float
    {
        $precision = $this->getPrecision($x) + $this->getPrecision($y);

        return round($x - $y, $precision);
    }

    private function getPrecision(float $float): int
    {
        $position = strpos((string) $float, '.');
        $segment  = $position === false
            ? null
            : substr((string) $float, $position + 1);

        return $segment !== null ? strlen($segment) : 0;
    }
}
