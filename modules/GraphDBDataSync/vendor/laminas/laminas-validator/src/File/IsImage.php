<?php

declare(strict_types=1);

namespace Laminas\Validator\File;

use Laminas\Validator\Exception\InvalidArgumentException;

/**
 * Validator which checks if the file is an image
 *
 * @psalm-import-type OptionsArgument from MimeType
 */
final class IsImage extends MimeType
{
    public const FALSE_TYPE   = 'fileIsImageFalseType';
    public const NOT_DETECTED = 'fileIsImageNotDetected';
    public const NOT_READABLE = 'fileIsImageNotReadable';

    /**
     * @link https://www.iana.org/assignments/media-types/media-types.xhtml#image
     */
    private const DEFAULT_TYPES = [
        'application/cdf',
        'application/dicom',
        'application/fractals',
        'application/postscript',
        'application/vnd.hp-hpgl',
        'application/vnd.oasis.opendocument.graphics',
        'application/x-cdf',
        'application/x-cmu-raster',
        'application/x-ima',
        'application/x-inventor',
        'application/x-koan',
        'application/x-portable-anymap',
        'application/x-world-x-3dmf',
        'image/bmp',
        'image/c',
        'image/cgm',
        'image/fif',
        'image/gif',
        'image/heic',
        'image/heif',
        'image/jpeg',
        'image/jpm',
        'image/jpx',
        'image/jp2',
        'image/naplps',
        'image/pjpeg',
        'image/png',
        'image/svg',
        'image/svg+xml',
        'image/tiff',
        'image/vnd.adobe.photoshop',
        'image/vnd.djvu',
        'image/vnd.fpx',
        'image/vnd.net-fpx',
        'image/webp',
        'image/x-cmu-raster',
        'image/x-cmx',
        'image/x-coreldraw',
        'image/x-cpi',
        'image/x-emf',
        'image/x-ico',
        'image/x-icon',
        'image/x-jg',
        'image/x-ms-bmp',
        'image/x-niff',
        'image/x-pict',
        'image/x-pcx',
        'image/x-png',
        'image/x-portable-anymap',
        'image/x-portable-bitmap',
        'image/x-portable-greymap',
        'image/x-portable-pixmap',
        'image/x-quicktime',
        'image/x-rgb',
        'image/x-tiff',
        'image/x-unknown',
        'image/x-windows-bmp',
        'image/x-xpmi',
    ];

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::FALSE_TYPE   => "File is no image, '%type%' detected",
        self::NOT_DETECTED => 'The mimetype could not be detected from the file',
        self::NOT_READABLE => 'File is not readable or does not exist',
    ];

    /**
     * Sets validator options
     *
     * @param OptionsArgument $options
     */
    public function __construct(array $options = [])
    {
        try {
            $types = self::resolveMimeType($options['mimeType'] ?? null);
        } catch (InvalidArgumentException) {
            $types = self::DEFAULT_TYPES;
        }

        $options['mimeType'] = $types;

        parent::__construct($options);
    }
}
