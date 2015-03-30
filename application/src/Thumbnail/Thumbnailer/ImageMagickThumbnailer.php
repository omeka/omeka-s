<?php
namespace Omeka\Thumbnail\Thumbnailer;

use Omeka\Thumbnail\Exception;
use Omeka\Thumbnail\Manager as ThumbnailManager;
use Omeka\Thumbnail\Thumbnailer\AbstractThumbnailer;

class ImageMagickThumbnailer extends AbstractThumbnailer
{
    const CONVERT_COMMAND = 'convert';

    /**
     * @var string Path to the ImageMagick "convert" command
     */
    protected $convertPath;

    /**
     * {@inheritDoc}
     */
    public function setOptions(array $options)
    {
        if (!isset($this->convertPath)) {
            $this->setConvertPath($this->getOption('imagemagick_dir'));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function create($strategy, $constraint, array $options = array())
    {
        $origPath = sprintf('%s[%s]', $this->source, $this->getOption('page', 0));

        switch ($strategy) {
            case 'square':
                $gravity = isset($options['gravity']) ? $options['gravity'] : 'center';
                $args = array(
                    '-background white',
                    '-flatten',
                    '-thumbnail ' . escapeshellarg(sprintf('%sx%s^', $constraint, $constraint)),
                    '-gravity ' .  escapeshellarg($gravity),
                    '-crop ' . escapeshellarg(sprintf('%sx%s+0+0', $constraint, $constraint)),
                    '+repage',
                );
                break;
            case 'default':
            default:
                $args = array(
                    '-background white',
                    '-flatten',
                    '-thumbnail ' . escapeshellarg(sprintf('%sx%s>', $constraint, $constraint)),
                );
        }

        $file = $this->getServiceLocator()->get('Omeka\TempFile');
        $tempPath = $file->getTempPath() . ThumbnailManager::EXTENSION;
        $file->delete();

        $command = sprintf(
            '%s %s %s %s',
            $this->convertPath,
            escapeshellarg($origPath),
            implode(' ', $args),
            escapeshellarg($tempPath)
        );

        exec($command, $output, $exitCode);
        if (0 !== $exitCode) {
            throw new Exception\CannotCreateThumbnailException;
        }

        return $tempPath;
    }

    /**
     * Set the path to the ImageMagick "convert" command.
     *
     * @param string $convertDir
     */
    public function setConvertPath($convertDir)
    {
        if ($convertDir) {
            // Validate the configured directory.
            $convertDir = realpath($convertDir);
            if (!$convertDir || !is_dir($convertDir)) {
                throw new Exception\InvalidThumbnailerException(
                    'ImageMagick error: invalid ImageMagick command directory.'
                );
            }
            $convertPath = sprintf('%s/%s', $convertDir, self::CONVERT_COMMAND);
            if (!is_executable($convertPath)) {
                throw new Exception\InvalidThumbnailerException(
                    'ImageMagick error: the ImageMagick command is not executable.'
                );
            }
            $command = sprintf('%s -version', $convertPath);
            exec($command, $output, $exitCode);
            if (0 !== $exitCode) {
                throw new Exception\InvalidThumbnailerException(
                    'ImageMagick error: invalid ImageMagick command.'
                );
            }
        } else {
            // Auto-detect the command using "which".
            $command = sprintf('which %s', escapeshellarg(self::CONVERT_COMMAND));
            exec($command, $output, $exitCode);
            if (0 !== $exitCode) {
                throw new Exception\InvalidThumbnailerException(
                    'ImageMagick error: cannot determine path to ImageMagick command.'
                );
            }
            $convertPath = $output[0];
        }
        $this->convertPath = $convertPath;
    }
}
