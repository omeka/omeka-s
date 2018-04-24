<?php
namespace Omeka\File;

use finfo;
use Omeka\File\Store\StoreInterface;
use Zend\Math\Rand;

class TempFile
{
    /**
     * Map of nonstandard-to-standard media types.
     */
    const MEDIA_TYPE_ALIASES = [
        // application/ogg
        'application/x-ogg' => 'application/ogg',
        // application/rtf
        'text/rtf' => 'application/rtf',
        // audio/midi
        'audio/mid' => 'audio/midi',
        'audio/x-midi' => 'audio/midi',
        // audio/mpeg
        'audio/mp3' => 'audio/mpeg',
        'audio/mpeg3' => 'audio/mpeg',
        'audio/x-mp3' => 'audio/mpeg',
        'audio/x-mpeg' => 'audio/mpeg',
        'audio/x-mpeg3' => 'audio/mpeg',
        'audio/x-mpegaudio' => 'audio/mpeg',
        'audio/x-mpg' => 'audio/mpeg',
        // audio/ogg
        'audio/x-ogg' => 'audio/ogg',
        // audio/x-aac
        'audio/aac' => 'audio/x-aac',
        // audio/x-aiff
        'audio/aiff' => 'audio/x-aiff',
        // audio/x-ms-wma
        'audio/x-wma' => 'audio/x-ms-wma',
        'audio/wma' => 'audio/x-ms-wma',
        // audio/mp4
        'audio/x-mp4' => 'audio/mp4',
        'audio/x-m4a' => 'audio/mp4',
        // audio/x-wav
        'audio/wav' => 'audio/x-wav',
        // image/bmp
        'image/x-ms-bmp' => 'image/bmp',
        // image/x-icon
        'image/icon' => 'image/x-icon',
        // video/mp4
        'video/x-m4v' => 'video/mp4',
        // video/x-ms-asf
        'video/asf' => 'video/x-ms-asf',
        // video/x-ms-wmv
        'video/wmv' => 'video/x-ms-wmv',
        // video/x-msvideo
        'video/avi' => 'video/x-msvideo',
        'video/msvideo' => 'video/x-msvideo',
    ];

    /**
     * @var StoreInterface
     */
    protected $store;

    /**
     * @var ThumbnailManager
     */
    protected $thumbnailManager;

    /**
     * @var string Directory where to save this temporary file
     */
    protected $tempDir;

    /**
     * @var string Path to this temprorary file
     */
    protected $tempPath;

    /**
     * @var array Media type map
     */
    protected $mediaTypeMap;

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
     * @param string $tempDir
     * @param array $mediaTypeMap
     * @param StoreInterface $store
     * @param ThumbnailManager $thumbnailManager
     */
    public function __construct($tempDir, array $mediaTypeMap,
        StoreInterface $store, ThumbnailManager $thumbnailManager
    ) {
        $this->tempDir = $tempDir;
        $this->mediaTypeMap = $mediaTypeMap;
        $this->store = $store;
        $this->thumbnailManager = $thumbnailManager;

        // Always create a new, uniquely named temporary file.
        $this->setTempPath(tempnam($tempDir, 'omeka'));
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
        if (null === $extension) {
            $extension = $this->getExtension(); // could return null
        }
        if (null !== $extension) {
            $extension = ".$extension";
        }
        if (null === $tempPath) {
            $tempPath = $this->getTempPath();
        }
        $storagePath = sprintf('%s/%s%s', $prefix, $this->getStorageId(), $extension);
        $this->store->put($tempPath, $storagePath);
        return $storagePath;
    }

    /**
     * Store this as an "original" file.
     *
     * @return string The path of the stored file
     */
    public function storeOriginal()
    {
        return $this->store('original');
    }

    /**
     * Store this as an "asset" file.
     *
     * @return string The path of the stored file
     */
    public function storeAsset()
    {
        return $this->store('asset');
    }

    /**
     * Create and store thumbnail derivatives of this file.
     *
     * @return bool Whether thumbnails were created and stored
     */
    public function storeThumbnails()
    {
        $thumbnailer = $this->thumbnailManager->buildThumbnailer();

        $tempPaths = [];

        try {
            $thumbnailer->setSource($this);
            $thumbnailer->setOptions($this->thumbnailManager->getThumbnailerOptions());
            foreach ($this->thumbnailManager->getTypeConfig() as $type => $config) {
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
            $this->store($type, 'jpg', $tempPath);
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
        if (array_key_exists($mediaType, self::MEDIA_TYPE_ALIASES)) {
            $mediaType = self::MEDIA_TYPE_ALIASES[$mediaType];
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
     * null if the file has no source name or the file has no extension and the
     * media type cannot be mapped to an extension.
     *
     * @return string|null
     */
    public function getExtension()
    {
        if (isset($this->extension)) {
            return $this->extension;
        }
        $extension = null;
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
        if (null === $extension) {
            $mediaType = $this->getMediaType();
            if (isset($this->mediaTypeMap[$mediaType][0])) {
                $extension = strtolower($this->mediaTypeMap[$mediaType][0]);
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
     * Get the size of the file in bytes.
     *
     * @uses filesize
     * @return string
     */
    public function getSize()
    {
        return filesize($this->getTempPath());
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
