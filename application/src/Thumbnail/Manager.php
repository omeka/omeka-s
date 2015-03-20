<?php
namespace Omeka\Thumbnail;

use Omeka\Thumbnail\Exception;
use Omeka\Thumbnail\ThumbnailerInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Manager implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var array Default thumbnail configuration
     */
    protected $thumbnails = array(
        'large' => array(
            'constraint' => 800,
            'type' => 'default',
        ),
        'medium' => array(
            'constraint' => 200,
            'type' => 'default',
        ),
        'square' => array(
            'constraint' => 200,
            'type' => 'square',
        ),
    );

    /**
     * Set the custom thumbnail configuration during construction.
     *
     * @param array $thumbnails Custom thumbnail configuration
     */
    public function __construct(array $thumbnails)
    {
        foreach ($thumbnails as $thumbnail => $info) {
            if (!isset($info['constraint'])) {
                continue;
            }
            $this->thumbnails[$thumbnail]['constraint'] = (int) $info['constraint'];
            if (isset($info['type'])) {
                $this->thumbnails[$thumbnail]['type'] = $info['type'];
            } else {
                $this->thumbnails[$thumbnail]['type'] = 'default';
            }
        }
    }

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
        foreach ($this->thumbnails as $thumbnail => $info) {
            $tempPaths[$thumbnail] = $thumbnailer->create($info['type'], $info['constraint']);
        }

        // Finally, store the thumbnails.
        $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
        foreach ($tempPaths as $thumbnail => $tempPath) {
            $storagePath = sprintf('/%s/%s.jpeg', $thumbnail, $storageBaseName);
            $fileStore->put($tempPath, $storagePath);
        }
        */
    }
}
