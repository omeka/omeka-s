<?php
namespace ExtractMetadata\Extractor;

use Omeka\Stdlib\Cli;

/**
 * Use Apache Tika to extract metadata.
 *
 * @see https://tika.apache.org/
 */
class Tika implements ExtractorInterface
{
    protected $cli;

    protected $config;

    public function __construct(Cli $cli, array $config)
    {
        $this->cli = $cli;
        $this->config = $config;
    }

    public function getLabel()
    {
        return 'Tika';
    }

    public function isAvailable()
    {
        $hasJava = (bool) $this->cli->getCommandPath('java');
        $hasTika = @is_file($this->config['jar_path']);
        return ($hasJava && $hasTika);
    }

    public function supports($mediaType)
    {
        // tika can extract from an unspecified amount of media types.
        return true;
    }

    public function extract($filePath, $mediaType)
    {
        $commandPath = $this->cli->getCommandPath('java');
        if (false === $commandPath) {
            return false;
        }
        $command = sprintf('%s -jar %s --json %s', $commandPath, $this->config['jar_path'], $filePath);
        $metadata = json_decode($this->cli->execute($command), true);
        return $metadata;
    }
}
