<?php
namespace Omeka\Thumbnail;

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
        // Set custom type configuration.
        foreach ($types as $type => $config) {
            if (!isset($config['constraint'])) {
                continue;
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
     * Gets a new thumbnailer instance for each call to this method (via a
     * non-shared service). This gives thumbnailers an opportunity to share
     * common properties that are needed for each type. Depending on the how the
     * strategy creates thumbnails, this can solve memory limit problems.
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
