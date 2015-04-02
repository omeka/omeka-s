<?php
namespace Omeka\File;

use Omeka\File\Store\StoreInterface;
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
        'store' => 'Omeka\File\LocalStore',
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
        if (isset($config['store']) && is_string($config['store'])) {
            $this->config['store'] = $config['store'];
        }

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
     * Get the file store service.
     *
     * @return StoreInterface
     */
    public function getStore()
    {
        return $this->getServiceLocator()->get($this->config['store']);
    }

    /**
     * Store original file.
     *
     * @param File $file
     */
    public function storeOriginal(File $file)
    {
        $storagePath = $this->getStoragePath(
            self::ORIGINAL_PREFIX,
            $file->getStorageName()
        );
        $this->getStore()->put($file->getTempPath(), $storagePath);
    }

    /**
     * Delete original file.
     *
     * @param Media $media
     */
    public function deleteOriginal(Media $media)
    {
        $storagePath = $this->getStoragePath(
            self::ORIGINAL_PREFIX,
            $media->getFilename()
        );
        $this->getStore()->delete($storagePath);
    }

    /**
     * Get the URI to the original file.
     *
     * @param Media $media
     * @return string
     */
    public function getOriginalUri(Media $media)
    {
        $storagePath = $this->getStoragePath(
            self::ORIGINAL_PREFIX,
            $media->getFilename()
        );

        return $this->getStore()->getUri($storagePath);
    }

    /**
     * Get the URI to the thumbnail file.
     *
     * @param string $type
     * @param Media $media
     * @return string
     */
    public function getThumbnailUri($type, Media $media)
    {
        $filename = $media->getFilename();
        $basename = strstr($filename, '.', true) ?: $filename;
        $storagePath = $this->getStoragePath(
            $type, $basename, self::THUMBNAIL_EXTENSION
        );

        return $this->getStore()->getUri($storagePath);
    }

    /**
     * Create and store thumbnail derivatives.
     *
     * Gets the thumbnailer from the service manager for each call to this
     * method. This gives thumbnailers an opportunity to be non-shared services,
     * which can be useful for resolving memory allocation issues.
     *
     * @param string $source
     * @param string $storageBaseName
     * @return bool Whether thumbnails were created and stored
     */
    public function storeThumbnails(File $file)
    {
        $thumbnailer = $this->getServiceLocator()->get('Omeka\File\Thumbnailer');
        $tempPaths = array();

        try {
            $thumbnailer->setSource($file->getTempPath());
            $thumbnailer->setOptions($this->config['thumbnail_options']);
            foreach ($this->config['thumbnail_types'] as $type => $config) {
                $tempPaths[$type] = $thumbnailer->create(
                    $config['strategy'], $config['constraint'], $config['options']
                );
            }
        } catch (Exception\CannotCreateThumbnailException $e) {
            // Delete temporary files created before exception was thrown.
            foreach ($tempPaths as $tempPath) {
                @unlink($tempPath);
            }
            return false;
        }

        // Finally, store the thumbnails.
        foreach ($tempPaths as $type => $tempPath) {
            $storagePath = $this->getStoragePath(
                $type, $file->getStorageBaseName(), self::THUMBNAIL_EXTENSION
            );
            $this->getStore()->put($tempPath, $storagePath);
            // Delete the temporary file in case the file store hasn't already.
            @unlink($tempPath);
        }

        return true;
    }

    /**
     * Check whether a thumbnail type exists.
     *
     * @param string $type
     * @return bool
     */
    public function thumbnailTypeExists($type)
    {
        return array_key_exists($type, $this->config['thumbnail_types']);
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

    /**
     * Get a storage path.
     *
     * @param string $prefix The storage prefix
     * @param string $name The file name, or basename if extension is passed
     * @param null|string $extension The file extension
     * @return string
     */
    public function getStoragePath($prefix, $name, $extension = null)
    {
        return sprintf('%s/%s%s', $prefix, $name, $extension ? ".$extension" : null);
    }
}
