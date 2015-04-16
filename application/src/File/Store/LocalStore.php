<?php
namespace Omeka\File\Store;

use Omeka\File\Exception;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Local filesystem file store
 */
class LocalStore implements StoreInterface
{
    use ServiceLocatorAwareTrait;

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
            throw new Exception\RuntimeException(
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
        $this->assurePathDirectories($localPath);
        $status = rename($source, $localPath);
        chmod($localPath, 0644);
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
        if (!file_exists($localPath)) {
            $this->getServiceLocator()->get('Omeka\Logger')->warn(sprintf(
                'Cannot delete file; file does not exist %s', $localPath
            ));
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
        if (preg_match('#(?:^|/)\.{2}(?:$|/)#', $storagePath)) {
            throw new Exception\RuntimeException(
                sprintf('Illegal relative component in path "%s"',
                    $storagePath));
        }
        return $this->basePath . DIRECTORY_SEPARATOR . $storagePath;
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
