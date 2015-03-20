<?php
namespace Omeka\Thumbnail;

use Zend\ServiceManager\ServiceLocatorAwareInterface;

interface ThumbnailerInterface extends ServiceLocatorAwareInterface 
{
    /**
     * Set the file source (typically path to temporary file).
     *
     * @param string $source
     */
    public function setSource($source);

    /**
     * Create a thumbnail derivative.
     *
     * Implementations should attempt to copy the source file, convert it to
     * JPEG, and resize it according to the passed type and constraint. They
     * should handle at least the "default" and "square" thumbnail types.
     *
     * @param string $type Type of thumbnail (default is "default")
     * @param int $constraint Size constraint of the thumbnail
     * @return string Path to the new thumnail file
     */
    public function create($type, $constraint);
}
