<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\Entity\MediaRepresentation;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Exception;
use Zend\View\Helper\AbstractHelper;

class Media extends AbstractHelper
{
    /**
     * @var array
     */
    protected $mediaTypes;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $this->mediaTypes = $config['media_types'];
    }

    /**
     * Return the HTML necessary to render an add/edit form.
     *
     * @param string|MediaRepresentation $mediaType
     * @param array $options Global options for the media type
     * @return string
     */
    public function form($mediaType, array $options = array())
    {
        $media = null;
        if ($mediaType instanceof MediaRepresentation) {
            $media = $mediaType;
            $mediaType = $media->getType();
        }
        return $this->getMediaType($mediaType)->form($media, $options);
    }

    /**
     * Return the HTML necessary to render the provided media.
     *
     * @param MediaRepresentation $media
     * @param array $options Global options for the media type
     * @return string
     */
    public function render(MediaRepresentation $media, array $options = array())
    {
        return $this->getMediaType($media->getType())->render($media, $options);
    }

    /**
     * Get the media type object.
     *
     * @param string $mediaType
     * @return MediaTypeInterface
     */
    protected function getMediaType($mediaType)
    {
        if (!isset($this->mediaTypes[$mediaType])) {
            throw new Exception\InvalidArgumentException('Media type not registered.');
        }
        if (!class_exists($this->mediaTypes[$mediaType])) {
            throw new Exception\RuntimeException('Media type class does not exist.');
        }
        return new $this->mediaTypes[$mediaType];
    }
}
