<?php
namespace Omeka\File;

use finfo;
use Omeka\File\Manager as FileManager;
use Omeka\Service\Exception\ConfigException;
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
     * @var string Name of the stored file (with extension).
     */
    protected $storageName;

    /**
     * @var string Internet media type of the file
     */
    protected $mediaType;

    /**
     * @var string Filename extension of the file
     */
    protected $extension;

    /**
     * Get the path to the temporary file.
     *
     * @param null|string $tempDir
     * @return string
     */
    public function getTempPath($tempDir = null)
    {
        if (isset($this->tempPath)) {
            return $this->tempPath;
        }
        if (!isset($tempDir)) {
            $config = $this->getServiceLocator()->get('Config');
            if (!isset($config['temp_dir'])) {
                throw new ConfigException('Missing temporary directory configuration');
            }
            $tempDir = $config['temp_dir'];
        }
        $this->tempPath = tempnam($tempDir, 'omeka');
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

    public function getStorageName()
    {
        if (isset($this->storageName)) {
            return $this->storageName;
        }
        $extension = $this->getExtension();
        $this->storageName = sprintf('%s%s', $this->getStorageBaseName(),
            $extension ? ".$extension" : null);
        return $this->storageName;
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
     * Get the filename extension for the original file.
     *
     * Checks the extension against a map of Internet media types. Returns a
     * "best guess" extension if the media type is known but the original
     * extension is unrecognized or nonexistent. Returns the original extension
     * if it is unrecoginized, maps to a known media type, or maps to the
     * catch-all media type, "application/octet-stream".
     *
     * @return string
     */
    public function getExtension()
    {
        if (isset($this->extension)) {
            return $this->extension;
        }

        if (!isset($this->sourceName)) {
            return null;
        }

        $mediaTypeMap = $this->getServiceLocator()->get('Omeka\File\MediaTypeMap');
        $mediaType = $this->getMediaType();
        $extension = substr(strrchr($this->sourceName, '.'), 1);

        if (isset($mediaTypeMap[$mediaType][0])
            && !in_array($mediaType, array('application/octet-stream'))
        ) {
            if ($extension) {
                if (!in_array($extension, $mediaTypeMap[$mediaType])) {
                    // Unrecognized extension.
                    $extension = $mediaTypeMap[$mediaType][0];
                }
            } else {
                // No extension.
                $extension = $mediaTypeMap[$mediaType][0];
            }
        }

        $this->extension = $extension;
        return $extension;
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
