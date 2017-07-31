<?php
namespace Omeka\File;

class Manager
{
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
     * Get all thumbnail types.
     *
     * @return array
     */
    public function getThumbnailTypes()
    {
        return array_keys($this->config['thumbnail_types']);
    }
}
