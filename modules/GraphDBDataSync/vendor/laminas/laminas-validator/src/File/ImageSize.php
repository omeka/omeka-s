<?php

declare(strict_types=1);

namespace Laminas\Validator\File;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception\InvalidArgumentException;

use function count;
use function getimagesize;

/**
 * Validator for the image size of an image file
 *
 * @psalm-type OptionsArgument = array{
 *     minWidth?: int|null,
 *     maxWidth?: int|null,
 *     minHeight?: int|null,
 *     maxHeight?: int|null,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class ImageSize extends AbstractValidator
{
    /**
     * @const string Error constants
     */
    public const WIDTH_TOO_BIG    = 'fileImageSizeWidthTooBig';
    public const WIDTH_TOO_SMALL  = 'fileImageSizeWidthTooSmall';
    public const HEIGHT_TOO_BIG   = 'fileImageSizeHeightTooBig';
    public const HEIGHT_TOO_SMALL = 'fileImageSizeHeightTooSmall';
    public const NOT_DETECTED     = 'fileImageSizeNotDetected';
    public const NOT_READABLE     = 'fileImageSizeNotReadable';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::WIDTH_TOO_BIG    => "Maximum allowed width for image should be '%maxwidth%' but '%width%' detected",
        self::WIDTH_TOO_SMALL  => "Minimum expected width for image should be '%minwidth%' but '%width%' detected",
        self::HEIGHT_TOO_BIG   => "Maximum allowed height for image should be '%maxheight%' but '%height%' detected",
        self::HEIGHT_TOO_SMALL => "Minimum expected height for image should be '%minheight%' but '%height%' detected",
        self::NOT_DETECTED     => 'The size of image could not be detected',
        self::NOT_READABLE     => 'File is not readable or does not exist',
    ];

    /** @var array<string, string|array<string, string>> */
    protected array $messageVariables = [
        'minwidth'  => 'minWidth',
        'maxwidth'  => 'maxWidth',
        'minheight' => 'minHeight',
        'maxheight' => 'maxHeight',
        'width'     => 'width',
        'height'    => 'height',
    ];

    /**
     * Detected width
     */
    protected int|null $width;

    /**
     * Detected height
     */
    protected int|null $height;

    protected readonly int $minWidth;
    protected readonly int|null $maxWidth;
    protected readonly int $minHeight;
    protected readonly int|null $maxHeight;

    /**
     * Sets validator options
     *
     * Accepts the following option keys:
     * - minHeight
     * - minWidth
     * - maxHeight
     * - maxWidth
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options)
    {
        $minWidth  = $options['minWidth'] ?? 0;
        $maxWidth  = $options['maxWidth'] ?? null;
        $minHeight = $options['minHeight'] ?? 0;
        $maxHeight = $options['maxHeight'] ?? null;

        if ($minWidth === 0 && $maxWidth === null && $minHeight === 0 && $maxHeight === null) {
            throw new InvalidArgumentException(
                'At least one size constraint is required',
            );
        }

        if (($minWidth > (int) $maxWidth) || ($minHeight > (int) $maxHeight)) {
            throw new InvalidArgumentException(
                'Max width or height must exceed the minimum equivalent',
            );
        }

        $this->minWidth  = $minWidth;
        $this->maxWidth  = $maxWidth;
        $this->minHeight = $minHeight;
        $this->maxHeight = $maxHeight;
        $this->width     = null;
        $this->height    = null;

        unset($options['minWidth'], $options['maxWidth'], $options['minHeight'], $options['maxHeight']);

        parent::__construct($options);
    }

    /**
     * Returns true if and only if the image size of $value is at least min and
     * not bigger than max
     */
    public function isValid(mixed $value): bool
    {
        $this->width  = null;
        $this->height = null;

        if (! FileInformation::isPossibleFile($value)) {
            $this->error(self::NOT_READABLE);
            return false;
        }

        $file = FileInformation::factory($value);

        if (! $file->readable) {
            $this->error(self::NOT_READABLE);
            return false;
        }

        $this->setValue($file->clientFileName ?? $file->baseName);

        $size = getimagesize($file->path);

        if ($size === false || ($size[0] === 0) || ($size[1] === 0)) {
            $this->error(self::NOT_DETECTED);
            return false;
        }

        $this->width  = $size[0];
        $this->height = $size[1];
        if ($this->width < $this->minWidth) {
            $this->error(self::WIDTH_TOO_SMALL);
        }

        if ($this->maxWidth !== null && $this->width > $this->maxWidth) {
            $this->error(self::WIDTH_TOO_BIG);
        }

        if ($this->height < $this->minHeight) {
            $this->error(self::HEIGHT_TOO_SMALL);
        }

        if ($this->maxHeight !== null && $this->height > $this->maxHeight) {
            $this->error(self::HEIGHT_TOO_BIG);
        }

        if (count($this->getMessages()) > 0) {
            return false;
        }

        return true;
    }
}
