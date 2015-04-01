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
