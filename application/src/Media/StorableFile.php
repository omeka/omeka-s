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
     * Store the file.
     *
     * @param string $originalName The original name of the file
     */
    public function store($originalName)
    {
        $extension = $this->getExtension($originalName);
        $storedName = $this->getStoredName($extension);

        $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
        $fileStore->put($this->getTempPath(), $storedName);
    }

    /**
     * Get the path to the temporary file.
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
