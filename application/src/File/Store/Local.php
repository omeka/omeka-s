<?php
namespace Omeka\File\Store;

use Omeka\File\Exception;
use Zend\Log\Logger;

/**
 * Local filesystem file store
 */
class Local implements StoreInterface
{
    /**
     * Local base path.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Base URI.
     *
     * @var string
     */
    protected $baseUri;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param string $basePath
     * @param string $baseUri
     */
    public function __construct($basePath, $baseUri, Logger $logger)
    {
        $this->basePath = realpath($basePath);
        $this->baseUri = $baseUri;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function put($source, $storagePath)
    {
        $localPath = $this->getLocalPath($storagePath);
        $this->assurePathDirectories($localPath);
        $status = copy($source, $localPath);
        if (!$status) {
            throw new Exception\RuntimeException(
                sprintf('Failed to copy "%s" to "%s".', $source, $localPath)
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete($storagePath)
    {
        $localPath = $this->getLocalPath($storagePath);
        if (!file_exists($localPath)) {
            $this->logger->warn(
                sprintf('Cannot delete file; file does not exist %s', $localPath)
            );
            return;
        }
        $status = unlink($localPath);
        if (!$status) {
            throw new Exception\RuntimeException(
                sprintf('Failed to delete "%s".', $localPath)
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getUri($storagePath)
    {
        return sprintf('%s/%s', $this->baseUri, $storagePath);
    }

    /**
     * Get an absolute local path from a storage path
     *
     * @param string $storagePath Storage path
     * @return string Local path
     */
    protected function getLocalPath($storagePath)
    {
        if (preg_match('#(?:^|/)\.{2}(?:$|/)#', $storagePath)) {
            throw new Exception\RuntimeException(
                sprintf('Illegal relative component in path "%s"', $storagePath)
            );
        }
        return sprintf('%s/%s', $this->basePath, $storagePath);
    }

    /**
     * Check for directory existence and access for a local path
     *
     * @param string $localPath
     */
    protected function assurePathDirectories($localPath)
    {
        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!is_writable($dir)) {
            throw new Exception\RuntimeException(
                sprintf('Directory "%s" is not writable.', $dir)
            );
        }
    }
}
