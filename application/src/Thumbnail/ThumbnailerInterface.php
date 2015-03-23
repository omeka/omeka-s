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
     * JPEG, and resize it according to the passed strategy and constraint. They
     * should handle at least the "default" and "square" thumbnail strategies.
     *
     * @param string $strategy Creation strategy (default is "default")
     * @param int $constraint Size constraint of the thumbnail
     * @param array $options Thumbnail options
     * @return string Path to temporary thumbnail file
     */
    public function create($strategy, $constraint, array $options = array());
}
