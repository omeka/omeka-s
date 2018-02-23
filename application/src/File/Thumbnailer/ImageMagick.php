<?php
namespace Omeka\File\Thumbnailer;

use Omeka\File\Exception;
use Omeka\File\TempFileFactory;
use Omeka\Stdlib\Cli;

class ImageMagick extends AbstractThumbnailer
{
    const CONVERT_COMMAND = 'convert';

    /**
     * @var string Path to the ImageMagick "convert" command
     */
    protected $convertPath;

    /**
     * @var Cli
     */
    protected $cli;

    /**
     * @var TempFileFactory
     */
    protected $tempFileFactory;

    /**
     * @param Cli $cli
     * @param TempFileFactory $tempFileFactory
     */
    public function __construct(Cli $cli, TempFileFactory $tempFileFactory)
    {
        $this->cli = $cli;
        $this->tempFileFactory = $tempFileFactory;
    }

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
    public function create($strategy, $constraint, array $options = [])
    {
        $origPath = sprintf('%s[%s]', $this->source, $this->getOption('page', 0));

        switch ($strategy) {
            case 'square':
                $gravity = isset($options['gravity']) ? $options['gravity'] : 'center';
                $args = [
                    '-background white',
                    '+repage',
                    '-alpha remove',
                    '-thumbnail ' . escapeshellarg(sprintf('%sx%s^', $constraint, $constraint)),
                    '-gravity ' .  escapeshellarg($gravity),
                    '-crop ' . escapeshellarg(sprintf('%sx%s+0+0', $constraint, $constraint)),
                ];
                break;
            case 'default':
            default:
                $args = [
                    '-background white',
                    '+repage',
                    '-alpha remove',
                    '-thumbnail ' . escapeshellarg(sprintf('%sx%s>', $constraint, $constraint)),
                ];
        }

        if ($this->getOption('autoOrient', true)) {
            array_unshift($args, '-auto-orient');
        }

        $tempFile = $this->tempFileFactory->build();
        $tempPath = sprintf('%s.%s', $tempFile->getTempPath(), 'jpg');
        $tempFile->delete();

        $commandArgs = [$this->convertPath];
        if ($this->sourceFile->getMediaType() == 'application/pdf') {
            $commandArgs[] = '-density 150';
        }
        $commandArgs[] = escapeshellarg($origPath);
        $commandArgs = array_merge($commandArgs, $args);
        $commandArgs[] = escapeshellarg($tempPath);

        $command = implode(' ', $commandArgs);
        $cli = $this->cli;
        $output = $cli->execute($command);
        if (false === $output) {
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
        $cli = $this->cli;
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
