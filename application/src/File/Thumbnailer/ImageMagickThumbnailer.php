<?php
namespace Omeka\File\Thumbnailer;

use Omeka\File\Exception;
use Omeka\File\Manager as FileManager;
use Omeka\File\Thumbnailer\AbstractThumbnailer;

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
        parent::setOptions($options);
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
                    '+repage',
                    '-alpha remove',
                    '-thumbnail ' . escapeshellarg(sprintf('%sx%s^', $constraint, $constraint)),
                    '-gravity ' .  escapeshellarg($gravity),
                    '-crop ' . escapeshellarg(sprintf('%sx%s+0+0', $constraint, $constraint)),
                );
                break;
            case 'default':
            default:
                $args = array(
                    '-background white',
                    '+repage',
                    '-alpha remove',
                    '-thumbnail ' . escapeshellarg(sprintf('%sx%s>', $constraint, $constraint)),
                );
        }

        $file = $this->getServiceLocator()->get('Omeka\File');
        $tempPath = sprintf('%s.%s', $file->getTempPath(), FileManager::THUMBNAIL_EXTENSION);
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
        $cli = $this->getServiceLocator()->get('Omeka\Cli');
        if ($convertDir) {
            $convertPath = $cli->validateCommand($convertDir, self::CONVERT_COMMAND);
            if (false === $convertPath) {
                throw new Exception\InvalidThumbnailerException('ImageMagick error: invalid ImageMagick command.');
            }
        } else {
            $convertPath = $cli->getCommandPath(self::CONVERT_COMMAND);
            if (false === $convertPath) {
                throw new Exception\InvalidThumbnailerException('ImageMagick error: cannot determine path to ImageMagick command.');
            }
        }
        $this->convertPath = $convertPath;
    }
}
