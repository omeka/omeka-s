<?php
namespace Omeka\File;

use Zend\ServiceManager\ServiceLocatorInterface;

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
     * Build a thumbnailer object.
     *
     * @return Omeka\File\Thumbnailer\ThumbnailerInterface
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
