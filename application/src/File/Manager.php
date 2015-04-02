<?php
namespace Omeka\File;

use Omeka\Model\Entity\Media;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Manager implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const ORIGINAL_PREFIX = 'original';

    const THUMBNAIL_EXTENSION = 'jpg';

    /**
     * Default configuration
     *
     * @var array
     */
    protected $config = array(
        'thumbnail_types' => array(
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
        ),
        'thumbnail_options' => array(
            'imagemagick_dir' => null,
            'page' => 0,
        ),
    );

    /**
     * Set custom configuration during construction.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (isset($config['thumbnail_types']) && is_array($config['thumbnail_types'])) {
            foreach ($config['thumbnail_types'] as $type => $typeConfig) {
                if (!isset($typeConfig['constraint'])) {
                    throw new Exception\InvalidArgumentException(sprintf(
                        'No constraint provided for the "%s" thumbnail type.', $type
                    ));
                }
                $this->config['thumbnail_types'][$type]['constraint'] = (int) $typeConfig['constraint'];
                if (isset($config['strategy'])) {
                    $this->config['thumbnail_types'][$type]['strategy'] = $typeConfig['strategy'];
                } else {
                    $this->config['thumbnail_types'][$type]['strategy'] = 'default';
                }
                if (isset($config['options']) && is_array($typeConfig['options'])) {
                    $this->config['thumbnail_types'][$type]['options'] = $typeConfig['options'];
                } else {
                    $this->config['thumbnail_types'][$type]['options'] = array();
                }
            }
        }

        if (isset($config['thumbnail_options']) && is_array($config['thumbnail_options'])) {
            foreach ($config['thumbnail_options'] as $key => $value) {
                $this->config['thumbnail_options'][$key] = $value;
            }
        }
    }

    /**
     * Store original file.
     *
     * @param File $file
     */
    public function storeOriginal(File $file)
    {
        $fileStore = $this->getServiceLocator()->get('Omeka\File\Store');
        $storagePath = $this->getStoragePath(
            self::ORIGINAL_PREFIX,
            $file->getStorageName()
        );
        $fileStore->put($file->getTempPath(), $storagePath);
    }

    /**
     * Delete original file.
     *
     * @param Media $media
     */
    public function deleteOriginal(Media $media)
    {
        $fileStore = $this->getServiceLocator()->get('Omeka\File\Store');
        $storagePath = $this->getStoragePath(
            self::ORIGINAL_PREFIX,
            $media->getFilename()
        );
        $fileStore->delete($storagePath);
    }

    /**
     * Get the URI to the original file.
     *
     * @param Media $media
     * @return string
     */
    public function getOriginalUri(Media $media)
    {
        $fileStore = $this->getServiceLocator()->get('Omeka\File\Store');
        $storagePath = $this->getStoragePath(
            self::ORIGINAL_PREFIX,
            $media->getFilename()
        );
        return $fileStore->getUri($storagePath);
    }

    /**
     * Get a storage path.
     *
     * @param string $prefix The storage prefix
     * @param string $name The file name, or basename if extension is passed
     * @param null|string $extension The file extension
     */
    public function getStoragePath($prefix, $name, $extension = null)
    {
        return sprintf('%s/%s%s', $prefix, $name, $extension);
    }
}
