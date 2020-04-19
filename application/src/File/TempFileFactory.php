<?php
namespace Omeka\File;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\ServiceManager\ServiceLocatorInterface;

class TempFileFactory implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    /**
     * @var string
     */
    protected $tempDir;

    /**
     * @var array
     */
    protected $mediaTypeMap;

    /**
     * @var \Omeka\File\Store\StoreInterface
     */
    protected $store;

    /**
     * @var \Omeka\File\ThumbnailManager
     */
    protected $thumbnailManager;

    /**
     * @var \Omeka\File\Validator
     */
    protected $validator;

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
        $this->validator = $services->get('Omeka\File\Validator');
    }

    /**
     * Build a temporary file object.
     *
     * @return TempFile
     */
    public function build()
    {
        $tempFile = new TempFile($this->tempDir, $this->mediaTypeMap,
            $this->store, $this->thumbnailManager, $this->validator
        );
        $tempFile->setEventManager($this->getEventManager());
        return $tempFile;
    }
}
