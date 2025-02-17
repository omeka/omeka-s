<?php
namespace ExtractMetadata\Extractor;

use Omeka\Stdlib\Cli;

/**
 * Use exiftool to extract text.
 *
 * @see https://exiftool.org/exiftool_pod.html
 */
class Exiftool implements ExtractorInterface
{
    protected $cli;

    public function __construct(Cli $cli)
    {
        $this->cli = $cli;
    }

    public function getLabel()
    {
        return 'ExifTool';
    }

    public function isAvailable()
    {
        return (bool) $this->cli->getCommandPath('exiftool');
    }

    public function supports($mediaType)
    {
        // exiftool can extract from an unspecified amount of media types.
        return true;
    }

    public function extract($filePath, $mediaType)
    {
        $commandPath = $this->cli->getCommandPath('exiftool');
        if (false === $commandPath) {
            return false;
        }
        $command = sprintf('%s -json -groupHeadings %s', $commandPath, $filePath);
        $metadata = json_decode($this->cli->execute($command), true)[0];
        return $metadata;
    }
}
