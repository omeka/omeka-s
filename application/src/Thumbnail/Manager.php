<?php
namespace Omeka\Thumbnail;

use Omeka\Thumbnail\Exception;
use Omeka\Thumbnail\ThumbnailerInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Manager implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const EXTENSION = '.jpeg';

    /**
     * @var array Default thumbnail configuration
     */
    protected $thumbnails = array(
        'large' => array(
            'strategy' => 'default',
            'constraint' => 800,
            'options' => array(),
        ),
        'medium' => array(
            'strategy' => 'default',
            'constraint' => 200,
            'options' => array(),
        ),
        'square' => array(
            'strategy' => 'square',
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
            if (isset($info['strategy'])) {
                $this->thumbnails[$thumbnail]['strategy'] = $info['strategy'];
            } else {
                $this->thumbnails[$thumbnail]['strategy'] = 'default';
            }
            if (isset($info['options']) && is_array($info['options'])) {
                $this->thumbnails[$thumbnail]['options'] = $info['options'];
            } else {
                $this->thumbnails[$thumbnail]['options'] = array();
            }
        }
    }

    /**
     * Check whether a thumbnail type exists.
     *
     * @param string $type
     * @return bool
     */
    public function typeExists($type)
    {
        return array_key_exists($type, $this->thumbnails);
    }

    /**
     * Get all thumbnail types.
     *
     * @return array
     */
    public function getTypes()
    {
        return array_keys($this->thumbnails);
    }

    /**
     * Get the storage path to a thumbnail.
     *
     * @param string $type
     * @param string $filename
     * @return string
     */
    public function getStoragePath($type, $filename)
    {
        // Remove an extension if there is one.
        $storageBaseName = strstr($filename, '.', true) ?: $filename;
        return sprintf('%s/%s%s', $type, $storageBaseName, self::EXTENSION);
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
                    $info['strategy'], $info['constraint'], $info['options']
                );
            }
        } catch (Exception\CannotCreateThumbnailException $e) {
            // @todo Unset thumbnail files created before exception was thrown
            return false;
        }

        // Finally, store the thumbnails.
        $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
        foreach ($tempPaths as $thumbnail => $tempPath) {
            $storagePath = $this->getStoragePath($thumbnail, $storageBaseName);
            $fileStore->put($tempPath, $storagePath);
        }

        return true;
    }
}
