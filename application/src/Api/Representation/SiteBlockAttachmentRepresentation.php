<?php
namespace Omeka\Api\Representation;

use Omeka\Entity\SiteBlockAttachment;
use Zend\ServiceManager\ServiceLocatorInterface;

class SiteBlockAttachmentRepresentation extends AbstractRepresentation
{
    /**
     * @var SiteBlockAttachment
     */
    protected $attachment;

    /**
     * Construct the attachment object.
     *
     * @param SiteBlockAttachment $attachment
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(SiteBlockAttachment $attachment, ServiceLocatorInterface $serviceLocator)
    {
        // Set the service locator first.
        $this->setServiceLocator($serviceLocator);
        $this->attachment = $attachment;
    }

    public function jsonSerialize()
    {
        $item = $this->item();
        $media = $this->media();
        return [
            'o:item' => $item ? $item->getReference() : null,
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
            ->getRepresentation($this->attachment->getItem());
    }

    /**
     * @return MediaRepresentation
     */
    public function media()
    {
        return $this->getAdapter('media')
            ->getRepresentation($this->attachment->getMedia());
    }

    /**
     * @return string
     */
    public function caption()
    {
        return $this->attachment->getCaption();
    }
}
