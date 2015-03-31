<?php
namespace Omeka\Thumbnail;

use Omeka\Stdlib\TempFile;
use Omeka\Thumbnail\Exception;
use Omeka\Thumbnail\ThumbnailerInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Manager implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const EXTENSION = '.jpg';

    /**
     * @var array Default thumbnail types and their configuration
     */
    protected $types = array(
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
     * @var array Default options for all thumbnail types
     */
    protected $options = array(
        'imagemagick_dir' => null,
        'page' => 0,
    );

    /**
     * Set the custom configuration during construction.
     *
     * @param array $types Thumbnail types and their configuration
     * @param array $options Options for all thumbnail types
     */
    public function __construct(array $types, array $options = array())
    {
        if (array_key_exists(TempFile::ORIGINAL_STORAGE_PREFIX, $types)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Cannot use the reserved word "%s" as a thumbnail type.',
                TempFile::ORIGINAL_STORAGE_PREFIX
            ));
        }

        // Set custom type configuration.
        foreach ($types as $type => $config) {
            if (!isset($config['constraint'])) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'No constraint provided for the "%s" thumbnail type.', $type
                ));
            }
            $this->types[$type]['constraint'] = (int) $config['constraint'];
            if (isset($config['strategy'])) {
                $this->types[$type]['strategy'] = $config['strategy'];
            } else {
                $this->types[$type]['strategy'] = 'default';
            }
            if (isset($config['options']) && is_array($config['options'])) {
                $this->types[$type]['options'] = $config['options'];
            } else {
                $this->types[$type]['options'] = array();
            }
        }
        // Set custom options.
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
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
        return array_key_exists($type, $this->types);
    }

    /**
     * Get all thumbnail types.
     *
     * @return array
     */
    public function getTypes()
    {
        return array_keys($this->types);
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
     * Gets the thumbnailer from the service manager for each call to this
     * method. This gives thumbnailers an opportunity to be non-shared services,
     * which can be useful for resolving memory allocation issues.
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
            $thumbnailer->setOptions($this->options);
            foreach ($this->types as $type => $config) {
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
        $fileStore = $this->getServiceLocator()->get('Omeka\FileStore');
        foreach ($tempPaths as $thumbnail => $tempPath) {
            $storagePath = $this->getStoragePath($thumbnail, $storageBaseName);
            $fileStore->put($tempPath, $storagePath);
            // Delete the temporary file in case the file store hasn't already.
            @unlink($tempPath);
        }

        return true;
    }
}
