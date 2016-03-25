<?php
namespace Omeka\File;

use finfo;
use Zend\Math\Rand;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class File implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var string Path to the temporary file
     */
    protected $tempPath;

    /**
     * @var string The name of the original source file
     */
    protected $sourceName;

    /**
     * @var string Base name of the stored file (without extension)
     */
    protected $storageBaseName;

    /**
     * @var string Internet media type of the file
     */
    protected $mediaType;

    /**
     * @var string $tempPath Local temporary path to the file
     */
    public function __construct($tempPath)
    {
        $this->setTempPath($tempPath);
    }

    /**
     * Set the path to the temporary file.
     *
     * Typically needed only when the temporary file already exists on the
     * server. Otherwise, use self::getTempPath() to seed a new temporary file.
     *
     * @param string $tempPath
     */
    public function setTempPath($tempPath)
    {
        $this->tempPath = $tempPath;
    }

    /**
     * Get the path to the temporary file.
     *
     * @param null|string $tempDir
     * @return string
     */
    public function getTempPath()
    {
        return $this->tempPath;
    }

    /**
     * Get the name/path of the source file.
     *
     * @return string
     */
    public function getSourceName()
    {
        return $this->sourceName;
    }

    /**
     * Set the name/path of the source file.
     *
     * @param string $sourceName
     */
    public function setSourceName($sourceName)
    {
        $this->sourceName = $sourceName;
    }

    /**
     * Get the base name of the persistently stored file.
     *
     * @return string
     */
    public function getStorageBaseName()
    {
        if (isset($this->storageBaseName)) {
            return $this->storageBaseName;
        }
        $this->storageBaseName = bin2hex(Rand::getBytes(20));
        return $this->storageBaseName;
    }

    /**
     * Get the Internet media type of the file.
     *
     * @uses finfo
     * @return string
     */
    public function getMediaType()
    {
        if (isset($this->mediaType)) {
            return $this->mediaType;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $this->mediaType = $finfo->file($this->getTempPath());
        return $this->mediaType;
    }

    /**
     * Delete this temporary file.
     *
     * Always delete a temporary file after all work has been done. Otherwise
     * the file will remain in the temporary directory.
     *
     * @return bool Whether the file was deleted/never created
     */
    public function delete()
    {
        if (isset($this->tempPath)) {
            return unlink($this->tempPath);
        }
        return true;
    }
}
