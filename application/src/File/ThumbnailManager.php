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
}
