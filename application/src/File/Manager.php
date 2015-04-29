<?php
namespace Omeka\File;

use Omeka\File\Store\StoreInterface;
use Omeka\File\Thumbnailer\ThumbnailerInterface;
use Omeka\Entity\Media;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Manager implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const ORIGINAL_PREFIX = 'original';

    const THUMBNAIL_EXTENSION = 'jpg';

    /**
     * @var array
     */
    protected $config;

    /**
     * Set configuration during construction.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get the file store service.
     *
     * @return StoreInterface
     */
    public function getStore()
    {
        return $this->getServiceLocator()->get($this->config['store']);
    }

    /**
     * Get the thumbnailer service.
     *
     * @return ThumbnailerInterface
     */
    public function getThumbnailer()
    {
        return $this->getServiceLocator()->get($this->config['thumbnailer']);
    }

    /**
     * Store original file.
     *
     * @param File $file
     */
    public function storeOriginal(File $file)
    {
        $storagePath = $this->getStoragePath(
            self::ORIGINAL_PREFIX,
            $file->getStorageName()
        );
        $this->getStore()->put($file->getTempPath(), $storagePath);
    }

    /**
     * Delete original file.
     *
     * @param Media $media
     */
    public function deleteOriginal(Media $media)
    {
        $storagePath = $this->getStoragePath(
            self::ORIGINAL_PREFIX,
            $media->getFilename()
        );
        $this->getStore()->delete($storagePath);
    }

    /**
     * Get the URL to the original file.
     *
     * @param Media $media
     * @return string
     */
    public function getOriginalUrl(Media $media)
    {
        $storagePath = $this->getStoragePath(
            self::ORIGINAL_PREFIX,
            $media->getFilename()
        );
        return $this->getStore()->getUri($storagePath);
    }

    /**
     * Create and store thumbnail derivatives.
     *
     * Gets the thumbnailer from the service manager for each call to this
     * method. This gives thumbnailers an opportunity to be non-shared services,
     * which can be useful for resolving memory allocation issues.
     *
     * @param string $source
     * @param string $storageBaseName
     * @return bool Whether thumbnails were created and stored
     */
    public function storeThumbnails(File $file)
    {
        $thumbnailer = $this->getThumbnailer();
        $tempPaths = array();

        try {
            $thumbnailer->setSource($file->getTempPath());
            $thumbnailer->setOptions($this->config['thumbnail_options']);
            foreach ($this->config['thumbnail_types'] as $type => $config) {
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
            $storagePath = $this->getStoragePath(
                $type, $file->getStorageBaseName(), self::THUMBNAIL_EXTENSION
            );
            $this->getStore()->put($tempPath, $storagePath);
            // Delete the temporary file in case the file store hasn't already.
            @unlink($tempPath);
        }

        return true;
    }

    /**
     * Delete thumbnail files.
     *
     * @param Media $media
     */
    public function deleteThumbnails(Media $media)
    {
        foreach ($this->getThumbnailTypes() as $type) {
            $storagePath = $this->getStoragePath(
                $type,
                $this->getBasename($media->getFilename()),
                self::THUMBNAIL_EXTENSION
            );
            $this->getStore()->delete($storagePath);
        }
    }

    /**
     * Get the URL to the thumbnail file.
     *
     * @param string $type
     * @param Media $media
     * @return string
     */
    public function getThumbnailUrl($type, Media $media)
    {
        if (!$media->hasThumbnails() || !$this->thumbnailTypeExists($type)) {

            $fallbacks = $this->config['thumbnail_fallbacks']['fallbacks'];
            $mediaType = $media->getMediaType();
            $topLevelType = strstr($mediaType, '/', true);

            if (isset($fallbacks[$mediaType])) {
                // Prioritize a match against the full media type, e.g. "image/jpeg"
                $fallback = $fallbacks[$mediaType];
            } elseif ($topLevelType && isset($fallbacks[$topLevelType])) {
                // Then fall back on a match against the top-level type, e.g. "image"
                $fallback = $fallbacks[$topLevelType];
            } else {
                $fallback = $this->config['thumbnail_fallbacks']['default'];
            }

            $assetUrl = $this->getServiceLocator()->get('ViewHelperManager')->get('assetUrl');
            return $assetUrl($fallback[0], $fallback[1]);
        }

        $storagePath = $this->getStoragePath(
            $type,
            $this->getBasename($media->getFilename()),
            self::THUMBNAIL_EXTENSION
        );
        return $this->getStore()->getUri($storagePath);
    }

    /**
     * Get all thumbnail URLs, keyed by type.
     *
     * @param Media $media
     * @return array
     */
    public function getThumbnailUrls(Media $media)
    {
        $urls = array();
        foreach ($this->getThumbnailTypes() as $type) {
            $urls[$type] = $this->getThumbnailUrl($type, $media);
        }
        return $urls;
    }

    /**
     * Check whether a thumbnail type exists.
     *
     * @param string $type
     * @return bool
     */
    public function thumbnailTypeExists($type)
    {
        return array_key_exists($type, $this->config['thumbnail_types']);
    }

    /**
     * Get all thumbnail types.
     *
     * @return array
     */
    public function getThumbnailTypes()
    {
        return array_keys($this->config['thumbnail_types']);
    }

    /**
     * Get a storage path.
     *
     * @param string $prefix The storage prefix
     * @param string $name The file name, or basename if extension is passed
     * @param null|string $extension The file extension
     * @return string
     */
    public function getStoragePath($prefix, $name, $extension = null)
    {
        return sprintf('%s/%s%s', $prefix, $name, $extension ? ".$extension" : null);
    }

    /**
     * Get the basename, given a file name.
     *
     * @param string $name
     * @return string
     */
    public function getBasename($name)
    {
        return strstr($name, '.', true) ?: $name;
    }
}
