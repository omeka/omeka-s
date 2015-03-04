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
     * Return the HTML necessary to render an add form.
     *
     * @param string $mediaType
     * @param array $options Global options for the media type
     * @return string
     */
    public function form($mediaType, array $options = array())
    {
        return $this->getMediaRenderer($mediaType)->form($this->getView(), $options);
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
        return $this->getMediaRenderer($media->type())->render($this->getView(), $media, $options);
    }

    /**
     * Get the media renderer object.
     *
     * @param string $mediaType
     * @return MediaTypeInterface
     */
    protected function getMediaRenderer($mediaType)
    {
        if (!isset($this->mediaTypes[$mediaType]['renderer'])) {
            throw new Exception\InvalidArgumentException('Media renderer not registered.');
        }
        if (!class_exists($this->mediaTypes[$mediaType]['renderer'])) {
            throw new Exception\RuntimeException('Media type renderer does not exist.');
        }
        return new $this->mediaTypes[$mediaType]['renderer'];
    }
}
