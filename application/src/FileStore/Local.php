<?php
namespace Omeka\FileStore;

/**
 * Local filesystem file store
 */
class Local implements FileStoreInterface
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
     * @param string $basePath
     * @param string $baseUri
     */
    public function __construct($basePath, $baseUri)
    {
        if (!(is_dir($basePath) && is_writable($basePath))) {
            throw Exception\RuntimeException(
                sprintf('Base path "%s" is not a writable directory.', $basePath)
            );
        }

        $this->basePath = realpath($basePath);
        $this->baseUri = $baseUri;
    }

    /**
     * {@inheritDoc}
     */
    public function put($source, $storagePath)
    {
        $localPath = $this->getLocalPath($storagePath);
        $status = rename($source, $localPath);
        if (!$status) {
            throw new Exception\RuntimeException(
                sprintf('Failed to move "%s" to "%s".', $source, $localPath)
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete($storagePath)
    {
        $localPath = $this->getLocalPath($storagePath);
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
        return $this->baseUri . '/' . $storagePath;
    }

    /**
     * Get an absolute local path from a storage path
     *
     * @param string $storagePath Storage path
     * @return string Local path
     */
    protected function getLocalPath($storagePath)
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $storagePath;
    }
}
