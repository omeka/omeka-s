<?php
namespace Omeka\File\Thumbnailer;

use Zend\ServiceManager\ServiceLocatorInterface;

class ThumbnailerFactory
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array "file_manager" configuration
     */
    protected $config;

    /**
     * @param ServiceLocatorInterface $services
     */
    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
        $config = $services->get('Config');
        $this->config = $config['file_manager'];
    }

    /**
     * Build a thumbnailer object.
     *
     * @return Omeka\File\Thumbnailer\ThumbnailerInterface
     */
    public function build()
    {
        return $this->services->build($this->config['thumbnailer']);
    }

    /**
     * Get the thumbnail options configuration.
     *
     * @return array
     */
    public function getThumbnailOptions()
    {
        return $this->config['thumbnail_options'];
    }

    /**
     * Get the thumbnail types configuration.
     *
     * @return array
     */
    public function getThumbnailTypes()
    {
        return $this->config['thumbnail_types'];
    }
}
