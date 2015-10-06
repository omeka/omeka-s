<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Exception;
use Omeka\Entity\SiteBlockAttachment;
use Zend\ServiceManager\ServiceLocatorInterface;

class SiteBlockAttachmentRepresentation extends AbstractRepresentation
{
    /**
     * Construct the attachment object.
     *
     * @param mixed $data
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct($data, ServiceLocatorInterface $serviceLocator)
    {
        // Set the service locator first.
        $this->setServiceLocator($serviceLocator);
        $this->setData($data);
    }

    /**
     * @var array
     */
    public function validateData($data)
    {
        if (!$data instanceof SiteBlockAttachment) {
            throw new Exception\InvalidArgumentException(
                $this->getTranslator()->translate(sprintf(
                    'Invalid data sent to %s.', get_called_class()
                ))
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        $media = $this->media();
        return [
            'o:item' => $this->item()->getReference(),
            'o:media' => $media ? $media->getReference() : null,
            'o:caption' => $this->caption(),
        ];
    }

    /**
     * @return ItemRepresentation
     */
    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation(null, $this->getData()->getItem());
    }

    /**
     * @return MediaRepresentation
     */
    public function media()
    {
        return $this->getAdapter('media')
            ->getRepresentation(null, $this->getData()->getMedia());
    }

    /**
     * @return string
     */
    public function caption()
    {
        return $this->getData()->getCaption();
    }
}
