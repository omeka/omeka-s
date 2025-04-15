<?php

declare(strict_types=1);

namespace Laminas\Validator\File;

use Laminas\Validator\Exception\InvalidArgumentException;

/**
 * Validator which checks if a file looks like a compressed archive
 *
 * @psalm-import-type OptionsArgument from MimeType
 */
final class IsCompressed extends MimeType
{
    public const FALSE_TYPE   = 'fileIsCompressedFalseType';
    public const NOT_DETECTED = 'fileIsCompressedNotDetected';
    public const NOT_READABLE = 'fileIsCompressedNotReadable';

    private const DEFAULT_TYPES = [
        'application/arj',
        'application/gnutar',
        'application/lha',
        'application/lzx',
        'application/vnd.ms-cab-compressed',
        'application/x-ace-compressed',
        'application/x-arc',
        'application/x-archive',
        'application/x-arj',
        'application/x-bzip',
        'application/x-bzip2',
        'application/x-cab-compressed',
        'application/x-compress',
        'application/x-compressed',
        'application/x-cpio',
        'application/x-debian-package',
        'application/x-eet',
        'application/x-gzip',
        'application/x-java-pack200',
        'application/x-lha',
        'application/x-lharc',
        'application/x-lzh',
        'application/x-lzma',
        'application/x-lzx',
        'application/x-rar',
        'application/x-sit',
        'application/x-stuffit',
        'application/x-tar',
        'application/zip',
        'application/x-zip',
        'application/zoo',
        'multipart/x-gzip',
    ];

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::FALSE_TYPE   => "File is not compressed, '%type%' detected",
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
