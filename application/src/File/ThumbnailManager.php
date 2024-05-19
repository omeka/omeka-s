<?php
namespace Omeka\File;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Representation\MediaRepresentation;

class ThumbnailManager
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array "thumbnails" configuration
     */
    protected $config;

    /**
     * @param ServiceLocatorInterface $services
     */
    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
        $config = $services->get('Config');
        $this->validateConfig($config);
        $this->config = $config['thumbnails'];
    }

    /**
     * Get the URL to a thumbnail image of a media.
     *
     * @param MediaRepresentation $media
     * @param string $type The type of thumbnail
     * @return string
     */
    public function thumbnailUrl(MediaRepresentation $media, $type)
    {
        if (!$media->hasThumbnails() || !$this->typeExists($type)) {
            $fallbacks = $this->getFallbacks();
            $mediaType = $media->mediaType();
            $topLevelType = strtok((string) $mediaType, '/');

            if (isset($fallbacks[$mediaType])) {
                // Prioritize a match against the full media type, e.g. "image/jpeg"
                $fallback = $fallbacks[$mediaType];
            } elseif ($topLevelType && isset($fallbacks[$topLevelType])) {
                // Then fall back on a match against the top-level type, e.g. "image"
                $fallback = $fallbacks[$topLevelType];
            } else {
                $fallback = $this->getDefaultFallback();
            }

            $assetUrl = $this->services->get('ViewHelperManager')->get('assetUrl');
            return $assetUrl($fallback[0], $fallback[1], true, false, true);
        }

        return $media->getFileUrl($type, $media->storageId(), 'jpg');
    }

    /**
     * Get all thumbnail URLs of a media, keyed by type.
     *
     * @param MediaRepresentation $media
     * @return array
     */
    public function thumbnailUrls(MediaRepresentation $media)
    {
        if (!$media->hasThumbnails()) {
            return [];
        }
        $urls = [];
        foreach ($this->getTypes() as $type) {
            $urls[$type] = $this->thumbnailUrl($media, $type);
        }
        return $urls;
    }

    /**
     * Build a thumbnailer object.
     *
     * @return \Omeka\File\Thumbnailer\ThumbnailerInterface
     */
    public function buildThumbnailer()
    {
        return $this->services->build('Omeka\File\Thumbnailer');
    }

    /**
     * Get the thumbnail type configuration.
     *
     * @return array
     */
    public function getTypeConfig()
    {
        return $this->config['types'];
    }

    /**
     * Get all thumbnail types.
     *
     * @return array
     */
    public function getTypes()
    {
        return array_keys($this->config['types']);
    }

    /**
     * Does the passed type exist?
     *
     * @return bool
     */
    public function typeExists($type)
    {
        return array_key_exists($type, $this->config['types']);
    }

    /**
     * Get the fallback thumbnails configuration.
     *
     * @return array
     */
    public function getFallbacks()
    {
        return $this->config['fallbacks']['fallbacks'];
    }

    /**
     * Get the default thumbnail fallback configuration.
     *
     * @return array
     */
    public function getDefaultFallback()
    {
        return $this->config['fallbacks']['default'];
    }

    /**
     * Get the thumbnailer options.
     *
     * @return array
     */
    public function getThumbnailerOptions()
    {
        return $this->config['thumbnailer_options'];
    }

    public function validateConfig($config)
    {
        if (!isset($config['thumbnails'])) {
            throw new Exception\ConfigException('Missing thumbnail configuration.'); // @translate
        }
        $config = $config['thumbnails'];
        if (!isset($config['types']) || !is_array($config['types'])) {
            throw new Exception\ConfigException('Missing thumbnail types configuration.'); // @translate
        }
        if (!isset($config['types']['large']) || !isset($config['types']['medium']) || !isset($config['types']['square'])) {
            throw new Exception\ConfigException('Missing the large, medium, or square thumbnail type configuration.'); // @translate
        }
        foreach ($config['types'] as $type) {
            if (!isset($type['constraint'])) {
                throw new Exception\ConfigException('Missing constraint for a thumbnail type configuration.'); // @translate
            }
        }
        if (!isset($config['fallbacks']) || !is_array($config['fallbacks'])) {
            throw new Exception\ConfigException('Missing thumbnail fallbacks configuration.'); // @translate
        }
        if (!isset($config['fallbacks']['default']) || !is_array($config['fallbacks']['default']) || 2 !== count($config['fallbacks']['default'])) {
            throw new Exception\ConfigException('Missing default thumbnail fallback configuration.'); // @translate
        }
        if (!isset($config['fallbacks']['fallbacks']) || !is_array($config['fallbacks']['fallbacks'])) {
            throw new Exception\ConfigException('Missing thumbnail fallback configuration.'); // @translate
        }
        foreach ($config['fallbacks']['fallbacks'] as $fallback) {
            if (!is_array($fallback) || 2 !== count($fallback)) {
                throw new Exception\ConfigException('Invalid thumbnail fallback configuration.'); // @translate
            }
        }
    }
}
