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
     * @var string Name of the persistently stored file
     */
    protected $storedName;

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
    public function store($originalName)
    {
        $extension = $this->getExtension($originalName);
        $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
        $fileStore->put($this->getTempPath(), $this->getStoredName($extension));
    }

    /**
     * Create and store thumbnails of this file.
     *
     * @param string $originalName The original name of the file
     * @return bool Whether thumbnails were created and stored
     */
    public function storeThumbnails($originalName)
    {
        $extension = $this->getExtension($originalName);
        $manager = $this->getServiceLocator()->get('Omeka\ThumbnailManager');
        return $manager->create($this->getTempPath(), $this->getStoredName($extension));
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

    /**
     * Get the name of the persistently stored file.
     *
     * @param string $extension The filename extension to append
     * @return string
     */
    public function getStoredName($extension)
    {
        if (isset($this->storedName)) {
            return $this->storedName;
        }
        $storedName = bin2hex(Rand::getBytes(20));
        if ($extension) {
            $storedName .= '.' . $extension;
        }
        $this->storedName = $storedName;
        return $storedName;
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
