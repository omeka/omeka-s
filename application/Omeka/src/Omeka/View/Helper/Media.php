<?php
namespace Omeka\View\Helper;

use Doctrine\ORM\EntityManager;
use Zend\View\Helper\AbstractHelper;

class Media extends AbstractHelper
{
    /**
     * @var array
     */
    protected $mediaTypes;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Construct the helper.
     *
     * @param array $mediaTypes
     * @param EntityManager $entityManager
     */
    public function __construct(array $mediaTypes, EntityManager $entityManager)
    {
        $this->mediaTypes = $mediaTypes;
        $this->entityManager = $entityManager;
    }

    /**
     * Return the HTML necessary to render an add/edit form for the provided
     * media type.
     *
     * @param string $mediaType
     * @param array $options Global options for the media type
     * @param array|null $media If set, return an edit form
     * @return string
     */
    public function form($mediaType, array $options = array(), array $media = null)
    {
        if (is_array($media)) {
            $media = $this->getMedia($media);
        }
        return $this->getMediaType($mediaType)->form($options, $media);
    }

    /**
     * Return the HTML necessary to render the provided media.
     *
     * @param array $media
     * @param array $options Global options for the media type
     * @return string
     */
    public function render(array $media, array $options = array())
    {
        $media = $this->getMedia($media);
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
            throw new \Exception('Media type not registered.');
        }
        if (!class_exists($this->mediaTypes[$mediaType])) {
            throw new \Exception('Media type class does not exist.');
        }
        return new $this->mediaTypes[$mediaType];
    }

    /**
     * Get the Media entity.
     *
     * @param array $media
     * @return Media
     */
    protected function getMedia(array $media)
    {
        if (!isset($media['id'])) {
            throw new \Exception('Media not found.');
        }
        return $this->entityManager->find('Omeka\Model\Entity\Media', $media['id']);
    }
}
