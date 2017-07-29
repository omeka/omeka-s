<?php
namespace Omeka\File;

use finfo;
use Zend\Math\Rand;

class TempFile
{
    /**
     * @var Manager
     */
    protected $fileManager;

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
    protected $storageId;

    /**
     * @var string Internet media type of the file
     */
    protected $mediaType;

    /**
     * @var string Extension of the file
     */
    protected $extension;

    /**
     * @param Manager $fileManager
     */
    public function __construct(Manager $fileManager)
    {
        $this->fileManager = $fileManager;
        // Always create a new, uniquely named temporary file.
        $this->setTempPath(tempnam($fileManager->getTempDir(), 'omeka'));
    }

    /**
     * Set the path to the temporary file.
     *
     * Typically needed only when the temporary file already exists on the
     * server.
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
     * Get the storage ID.
     *
     * The storage ID is the base name (without extension) of the persistently
     * stored file.
     *
     * @return string
     */
    public function getStorageId()
    {
        if (isset($this->storageId)) {
            return $this->storageId;
        }
        $this->storageId = bin2hex(Rand::getBytes(20));
        return $this->storageId;
    }

    /**
     * Set the storage ID
     *
     * @param string $storageId
     */
    public function setStorageId($storageId)
    {
        $this->storageId = $storageId;
    }

    /**
     * Store a file.
     *
     * @param string $prefix The storage prefix
     * @param null|string $extension The file extension, if different file
     * @param null|string $tempPath The temp path, if different file
     * @return string The path of the stored file
     */
    public function store($prefix, $extension = null, $tempPath = null)
    {
        $extension = $extension ? $extension : $this->getExtension();
        $tempPath = $tempPath ? $tempPath : $this->getTempPath();
        $storagePath = $this->fileManager->getStoragePath(
            $prefix, $this->getStorageId(), $extension
        );
        $this->fileManager->getStore()->put($tempPath, $storagePath);
        return $storagePath;
    }

    /**
     * Store this as an "original" file.
     *
     * @return string The path of the stored file
     */
    public function storeOriginal()
    {
        return $this->store(Manager::ORIGINAL_PREFIX);
    }

    /**
     * Store this as an "asset" file.
     *
     * @return string The path of the stored file
     */
    public function storeAsset()
    {
        return $this->store(Manager::ASSET_PREFIX);
    }

    /**
     * Create and store thumbnail derivatives of this file.
     *
     * @return bool Whether thumbnails were created and stored
     */
    public function storeThumbnails()
    {
        $thumbnailer = $this->fileManager->getThumbnailer();
        $managerConfig = $this->fileManager->getConfig();
        $tempPaths = [];

        try {
            $thumbnailer->setSource($this);
            $thumbnailer->setOptions($managerConfig['thumbnail_options']);
            foreach ($managerConfig['thumbnail_types'] as $type => $config) {
                $tempPaths[$type] = $thumbnailer->create(
                    $config['strategy'], $config['constraint'], $config['options']
                );
            }
        } catch (Exception\CannotCreateThumbnailException $e) {
            // Delete temporary files created before exception was thrown.
            foreach ($tempPaths as $tempPath) {
                @unlink($tempPath);
            }
            return false;
        }

        // Finally, store the thumbnails.
        foreach ($tempPaths as $type => $tempPath) {
            $this->store($type, Manager::THUMBNAIL_EXTENSION, $tempPath);
           // Delete the temporary file in case the file store hasn't already.
            @unlink($tempPath);
        }

        return true;
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
        $mediaType = $finfo->file($this->getTempPath());
        if (array_key_exists($mediaType, Manager::MEDIA_TYPE_ALIASES)) {
            $mediaType = Manager::MEDIA_TYPE_ALIASES[$mediaType];
        }
        $this->mediaType = $mediaType;
        return $this->mediaType;
    }

    /**
     * Get the filename extension for the original file.
     *
     * Heuristically determines whether the passed file has an extension. The
     * source name must contain at least one dot, the source name must not end
     * with a dot, and the extension must not be over 12 characters.
     *
     * Returns the extension if found. Returns a "best guess" extension if the
     * media type is known but the original extension is not found. Returns
     * false if the file has no source name or the file has no extension and the
     * media type cannot be mapped to an extension.
     *
     * @return string|false
     */
    public function getExtension()
    {
        if (isset($this->extension)) {
            return $this->extension;
        }
        $extension = false;
        if (!$sourceName = $this->getSourceName()) {
            return $extension;
        }
        $dotPos = strrpos($sourceName, '.');
        if (false !== $dotPos) {
            $sourceNameLen = strlen($sourceName);
            $extensionPos = $dotPos + 1;
            if ($sourceNameLen !== $extensionPos && (12 >= $sourceNameLen - $extensionPos)) {
                $extension = strtolower(substr($sourceName, $extensionPos));
            }
        }
        if (false === $extension) {
            $mediaTypeMap = $this->fileManager->getMediaTypeMap();
            $mediaType = $this->getMediaType();
            if (isset($mediaTypeMap[$mediaType][0])) {
                $extension = strtolower($mediaTypeMap[$mediaType][0]);
            }
        }
        return $extension;
    }

    /**
     * Get the SHA-256 checksum of the file.
     *
     * @uses hash_file
     * @return string
     */
    public function getSha256()
    {
        return hash_file('sha256', $this->getTempPath());
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
