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
            'type' => 'default',
            'constraint' => 800,
            'options' => array(),
        ),
        'medium' => array(
            'type' => 'default',
            'constraint' => 200,
            'options' => array(),
        ),
        'square' => array(
            'type' => 'square',
            'constraint' => 200,
            'options' => array(
                'gravity' => 'center',
            ),
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
            if (isset($info['options']) && is_array($info['options'])) {
                $this->thumbnails[$thumbnail]['options'] = $info['options'];
            } else {
                $this->thumbnails[$thumbnail]['options'] = array();
            }
        }
    }

    /**
     * Create thumbnail derivatives.
     *
     * @param string $source
     * @param string $storageBaseName
     * @return bool Whether thumbnails were created and stored
     */
    public function create($source, $storageBaseName)
    {
        $thumbnailer = $this->getServiceLocator()->get('Omeka\Thumbnailer');
        $tempPaths = array();

        try {
            $thumbnailer->setSource($source);
            foreach ($this->thumbnails as $thumbnail => $info) {
                $tempPaths[$thumbnail] = $thumbnailer->create(
                    $info['type'], $info['constraint'], $info['options']
                );
            }
        } catch (Exception\CannotCreateThumbnailException $e) {
            return false;
        }

        // Finally, store the thumbnails.
        $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
        foreach ($tempPaths as $thumbnail => $tempPath) {
            $storagePath = sprintf('/%s/%s.jpeg', $thumbnail, $storageBaseName);
            $fileStore->put($tempPath, $storagePath);
        }

        return true;
    }
}
