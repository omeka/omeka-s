<?php
namespace Omeka\File;

use Omeka\File\Store\StoreInterface;
use Omeka\File\Thumbnailer\ThumbnailerInterface;
use Omeka\Model\Entity\Media;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Manager implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const ORIGINAL_PREFIX = 'original';

    const THUMBNAIL_EXTENSION = 'jpg';

    /**
     * Default configuration
     *
     * @var array
     */
    protected $config = array(
        'store' => 'Omeka\File\LocalStore',
        'thumbnailer' => 'Omeka\File\ImageMagickThumbnailer',
        'thumbnail_types' => array(
            'large' => array(
                'strategy' => 'default',
                'constraint' => 800,
                'options' => array(),
            ),
            'medium' => array(
                'strategy' => 'default',
                'constraint' => 200,
                'options' => array(),
            ),
            'square' => array(
                'strategy' => 'square',
                'constraint' => 200,
                'options' => array(
                    'gravity' => 'center',
                ),
            ),
        ),
        'thumbnail_options' => array(
            'imagemagick_dir' => null,
            'page' => 0,
        ),
        'thumbnail_fallbacks' => array(
            'default' => array('thumbnails/default.png', 'Omeka'),
            'fallbacks' => array(
                'image' => array('thumbnails/image.png', 'Omeka'),
                'video' => array('thumbnails/video.png', 'Omeka'),
                'audio' => array('thumbnails/audio.png', 'Omeka'),
            ),
        ),
    );

    /**
     * Set custom configuration during construction.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (isset($config['store']) && is_string($config['store'])) {
            $this->config['store'] = $config['store'];
        }

        if (isset($config['thumbnailer']) && is_string($config['thumbnailer'])) {
            $this->config['thumbnailer'] = $config['thumbnailer'];
        }

        if (isset($config['thumbnail_types']) && is_array($config['thumbnail_types'])) {
            foreach ($config['thumbnail_types'] as $type => $typeConfig) {
                if (!isset($typeConfig['constraint'])) {
                    throw new Exception\InvalidArgumentException(sprintf(
                        'No constraint provided for the "%s" thumbnail type.', $type
                    ));
                }
                $this->config['thumbnail_types'][$type]['constraint'] = (int) $typeConfig['constraint'];
                if (isset($config['strategy'])) {
                    $this->config['thumbnail_types'][$type]['strategy'] = $typeConfig['strategy'];
                } else {
                    $this->config['thumbnail_types'][$type]['strategy'] = 'default';
                }
                if (isset($config['options']) && is_array($typeConfig['options'])) {
                    $this->config['thumbnail_types'][$type]['options'] = $typeConfig['options'];
                } else {
                    $this->config['thumbnail_types'][$type]['options'] = array();
                }
            }
        }

        if (isset($config['thumbnail_options']) && is_array($config['thumbnail_options'])) {
            foreach ($config['thumbnail_options'] as $key => $value) {
                $this->config['thumbnail_options'][$key] = $value;
            }
        }

        if (isset($config['thumbnail_fallbacks']) && is_array($config['thumbnail_fallbacks'])) {
            if (isset($config['thumbnail_fallbacks']['default'])
                && is_array($config['thumbnail_fallbacks']['default'])
                && 2 == count($config['thumbnail_fallbacks']['default'])
            ) {
                $this->config['thumbnail_fallbacks']['default'] = $config['thumbnail_fallbacks']['default'];
            }
            if (isset($config['thumbnail_fallbacks']['fallbacks'])
                && is_array($config['thumbnail_fallbacks']['fallbacks'])
            ) {
                foreach ($config['thumbnail_fallbacks']['fallbacks'] as $mediaType => $assetConfig) {
                    if (is_array($assetConfig) && 2 == count($assetConfig)) {
                        $this->config['thumbnail_fallbacks']['fallbacks'][$mediaType] = $assetConfig;
                    }
                }
            }
        }
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
