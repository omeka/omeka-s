<?php
namespace Omeka\Thumbnail;

use Omeka\Thumbnail\Exception;
use Omeka\Thumbnail\ThumbnailerInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Manager implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const CONSTRAINT_LARGE = 800;
    const CONSTRAINT_MEDIUM = 200;
    const CONSTRAINT_SQUARE = 200;

    protected $types = array(
        'large' => self::CONSTRAINT_LARGE,
        'medium' => self::CONSTRAINT_MEDIUM,
    );

    /**
     * Create thumbnail derivatives.
     *
     * @param string $source
     * @param string $storageBaseName
     */
    public function create($source, $storageBaseName)
    {
        /*
        $thumbnailer = $this->getServiceLocator()->get('Omeka\Thumbnailer');
        $thumbnailer->setSource($source);

        $tempPaths = array();
        $tempPaths[] = $thumbnailer->createSquare(self::CONSTRAINT_SQUARE);

        foreach ($this->types as $type => $constraint) {
            $tempPaths[$type] = $thumbnailer->create($constraint);
        }

        // Finally, store the thumbnails.
        $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
        foreach ($tempPaths as $type => $tempPath) {
            $fileStore->put($tempPath, sprintf('/%s/%s.jpeg', $type, $storageBaseName));
        }
        */
    }

    public function addType($type, $constraint)
    {
        $this->types[$type] = $constraint;
    }
}
