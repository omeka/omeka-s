<?php declare(strict_types=1);

namespace Omeka\File\Thumbnailer;

use Omeka\File\Exception;
use Omeka\File\TempFileFactory;
use Omeka\Stdlib\Cli;

class Vips extends AbstractThumbnailer
{
    const VIPS_COMMAND = 'vips';

    /**
     * @var string|false Path to the "vips" command. False means unavailable.
     */
    protected $vipsPath;

    /**
     * Old means version < 8.6.
     *
     * @var bool
     */
    protected $isOldVips;

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

    public function setOptions(array $options)
    {
        parent::setOptions($options);
        if (is_null($this->vipsPath)) {
            $this->setVipsPath($this->getOption('vips_dir'));
        }
    }

    public function create($strategy, $constraint, array $options = [])
    {
        if ($this->vipsPath === false) {
            throw new Exception\CannotCreateThumbnailException;
        }

        if ($this->getIsOldVips()) {
            return $this->createWithOldVips($strategy, $constraint, $options);
        }

        $origPath = $this->source;

        // Available parameters on load.
        $mediaType = $this->sourceFile->getMediaType();
        $supportPages = [
            'application/pdf',
            'image/gif',
            'image/tiff',
            'image/webp',
        ];
        $supportDpi = [
            'application/pdf',
            'image/svg+xml',
        ];
        $supportBackground = [
            'application/pdf',
        ];
        // Other via magick: page, density.
        $loadOptions = [];
        if (in_array($mediaType, $supportPages)) {
            $page = (int) $this->getOption('page', 0);
            $loadOptions[] = "page=$page";
        }
        if (in_array($mediaType, $supportDpi)) {
            $loadOptions[] = 'dpi=150';
        }
        if (in_array($mediaType, $supportBackground)) {
            $loadOptions[] = 'background=255 255 255';
        }
        if (count($loadOptions)) {
            $origPath .= '[' . implode(',', $loadOptions) . ']';
        }

        if ($strategy === 'square') {
            $vipsCrop = [
                // "none" does not crop (default).
                // 'none',
                'low',
                'centre',
                'high',
                'attention',
                'entropy',
                // "all" does not crop as square.
                // 'all',
            ];
            $mapImagickToVips = [
                'northwest' => 'high',
                'north' => 'high',
                'northearth' => 'high',
                'west' => 'centre',
                'center' => 'centre',
                'east' => 'centre',
                'southwest' => 'low',
                'south' => 'low',
                'southeast' => 'low',
            ];
            $gravity = isset($options['gravity']) ? strtolower($options['gravity']) : 'attention';
            if (isset($mapImagickToVips[$gravity])) {
                $gravity = $mapImagickToVips[$gravity];
            } elseif (!in_array($gravity, $vipsCrop)) {
                $gravity = 'attention';
            }
            $crop = ' --crop ' . $gravity;
        } else {
            $crop = '';
        }

        $tempFile = $this->tempFileFactory->build();
        $tempPath = $tempFile->getTempPath() . '.jpg';
        $tempPathCommand = $tempPath . '[background=255 255 255,optimize-coding]';
        $tempFile->delete();

        $command = sprintf(
            '%s thumbnail %s %s %d --height %d%s%s --size both --linear --intent absolute',
            $this->vipsPath,
            escapeshellarg($origPath),
            escapeshellarg($tempPathCommand),
            (int) $constraint,
            (int) $constraint,
            $crop,
            $this->getOption('autoOrient', true) ? ' --no-rotate' : ''
        );

        $output = $this->cli->execute($command);
        if (false === $output) {
            throw new Exception\CannotCreateThumbnailException;
        }

        return $tempPath;
    }

    protected function createWithOldVips($strategy, $constraint, array $options = [])
    {
        // The command line vips is not pipable, so an intermediate file is
        // required when there are more than one operation.
        // So for old vips, use the basic thumbnailer currently.
        // @link https://libvips.github.io/libvips/API/current/using-cli.html
        // @see \ImageServer\ImageServer\Vips::transform()

        $origPath = $this->source;

        $crop = $strategy === 'square'
            ? ' --crop'
            : '';

        $tempFile = $this->tempFileFactory->build();
        $tempPath = $tempFile->getTempPath() . '.jpg';
        $tempFile->delete();

        $command = sprintf(
            '%sthumbnail --size=%dx%d%s --format=%s %s',
            $this->vipsPath,
            (int) $constraint,
            (int) $constraint,
            $crop,
            escapeshellarg($tempPath),
            escapeshellarg($origPath)
        );

        $output = $this->cli->execute($command);
        if (false === $output) {
            throw new Exception\CannotCreateThumbnailException;
        }

        return $tempPath;
    }

    /**
     * Set the path to the "vips" command.
     *
     * @param string $vipsDir
     */
    public function setVipsPath($vipsDir): self
    {
        $cli = $this->cli;
        if (is_null($vipsDir)) {
            $vipsPath = $cli->getCommandPath(self::VIPS_COMMAND);
            if (false === $vipsPath) {
                throw new Exception\InvalidThumbnailerException('Vips error: cannot determine path to vips command.');
            }
        } elseif ($vipsDir) {
            $vipsPath = $cli->validateCommand($vipsDir, self::VIPS_COMMAND);
            if (false === $vipsPath) {
                throw new Exception\InvalidThumbnailerException('Vips error: invalid vips command.');
            }
        } else {
            $vipsPath = false;
        }
        $this->vipsPath = $vipsPath;
        return $this;
    }

    public function setIsOldVips($isOldVips): self
    {
        $this->setIsOldVips = (bool) $isOldVips;
        return $this;
    }

    public function getIsOldVips(): bool
    {
        if (is_null($this->isOldVips)) {
            $version = (string) $this->cli->execute($this->vipsPath . ' --version');
            $this->setIsOldVips = version_compare($version, 'vips-8.6', '<');
        }
        return $this->setIsOldVips;
    }
}
