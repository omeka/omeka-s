<?php
namespace Omeka\Media;

use finfo;
use Zend\Math\Rand;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class StorableFile implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var string Path to the temporary file
     */
    protected $tempPath;

    /**
     * @var string Base name of the stored file (without extension)
     */
    protected $storageBaseName;

    /**
     * @var string Name of the stored file (with extension)
     */
    protected $storageName;

    /**
     * @var string Internet media type of the file
     */
    protected $mediaType;

    /**
     * @var string Filename extension of the original file
     */
    protected $extension;

    /**
     * Store this file.
     *
     * @param string $originalName The original name of the file
     */
    public function storeOriginal($originalName)
    {
        $extension = $this->getExtension($originalName);
        $storagePath = '/original/' . $this->getStorageName($extension);
        $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
        $fileStore->put($this->getTempPath(), $storagePath);
    }

    /**
     * Create and store thumbnails of this file.
     *
     * @return bool Whether thumbnails were created and stored
     */
    public function storeThumbnails()
    {
        $manager = $this->getServiceLocator()->get('Omeka\ThumbnailManager');
        return $manager->create($this->getTempPath(), $this->getStorageBaseName());
    }

    /**
     * Get the path to the temporary file.
     *
     * If a temporary path is not already set this automatically generates one
     * using the configured temporary directory in "temp_dir".
     *
     * @return string
     */
    public function getTempPath()
    {
        if (isset($this->tempPath)) {
            return $this->tempPath;
        }
        $tempDir = $this->getServiceLocator()->get('Config')['temp_dir'];
        $this->tempPath = tempnam($tempDir, 'omeka');
        return $this->tempPath;
    }

    /**
     * Set the path to the temporary file.
     *
     * Use this only when the temporary path is not the same that will be
     * automatically generated on the first call to self::getTempPath().
     *
     * @param string $tempPath
     */
    public function setTempPath($tempPath)
    {
        $this->tempPath = $tempPath;
    }

    public function getStorageBaseName()
    {
        if (isset($this->storageBaseName)) {
            return $this->storageBaseName;
        }
        $this->storageBaseName = bin2hex(Rand::getBytes(20));
        return $this->storageBaseName;
    }

    /**
     * Get the name of the persistently stored file.
     *
     * @param string $extension The filename extension to append
     * @return string
     */
    public function getStorageName($extension)
    {
        if (isset($this->storageName)) {
            return $this->storageName;
        }
        $this->storageName = sprintf(
            '%s%s',
            $this->getStorageBaseName(),
            $extension ? ".$extension" : null
        );
        return $this->storageName;
    }

    /**
     * Get the Internet media type of the file.
     *
     * @uses finfo
     * @param string $filename The path to a file
     * @return string
     */
    public function getMediaType()
    {
        if (isset($this->mediaType)) {
            return $this->mediaType;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mediaType = $finfo->file($this->getTempPath());
        $this->mediaType = $mediaType;
        return $mediaType;
    }

    /**
     * Get the filename extension of the original file.
     *
     * Returns the original extension if the file already has one. Otherwise it
     * returns the first extension found from a map between Internet media types
     * and extensions.
     *
     * @param string $originalFile The original file name
     * @return string
     */
    public function getExtension($originalFile)
    {
        if (isset($this->extension)) {
            return $this->extension;
        }
        $map = $this->getServiceLocator()->get('Omeka\MediaTypeExtensionMap');
        $mediaType = $this->getMediaType();
        $extension = substr(strrchr($originalFile, '.'), 1);
        if (!$extension && isset($map[$mediaType][0])) {
            $extension = $map[$mediaType][0];
        }
        $this->extension = $extension;
        return $extension;
    }
}
