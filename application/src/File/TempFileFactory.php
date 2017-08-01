<?php
namespace Omeka\File;

use Zend\ServiceManager\ServiceLocatorInterface;

class TempFileFactory
{
    /**
     * @var string
     */
    protected $tempDir;

    /**
     * @var array
     */
    protected $mediaTypeMap;

    /**
     * @var Omeka\File\Store\StoreInterface
     */
    protected $store;

    /**
     * @var Omeka\File\ThumbnailManager
     */
    protected $thumbnailManager;

    /**
     * @param ServiceLocatorInterface $services
     */
    public function __construct(ServiceLocatorInterface $services)
    {
        $config = $services->get('Config');
        $this->tempDir = $config['temp_dir'];
        $this->mediaTypeMap = $services->get('Omeka\File\MediaTypeMap');
        $this->store = $services->get('Omeka\File\Store');
        $this->thumbnailManager = $services->get('Omeka\File\ThumbnailManager');
    }

    /**
     * Build a temporary file object.
     *
     * @return TempFile
     */
    public function build()
    {
        return new TempFile($this->tempDir, $this->mediaTypeMap, $this->store, $this->thumbnailManager);
    }
}
