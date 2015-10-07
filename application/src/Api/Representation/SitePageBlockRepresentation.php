<?php
namespace Omeka\Api\Representation;

use Omeka\Entity\SitePageBlock;
use Zend\ServiceManager\ServiceLocatorInterface;

class SitePageBlockRepresentation extends AbstractRepresentation
{
    /**
     * @var SitePageBlock
     */
    protected $block;

    /**
     * Construct the block object.
     *
     * @param SitePageBlock $block
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(SitePageBlock $block, ServiceLocatorInterface $serviceLocator)
    {
        // Set the service locator first.
        $this->setServiceLocator($serviceLocator);
        $this->block = $block;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            'o:layout' => $this->layout(),
            'o:data' => $this->data(),
            'o:attachment' => $this->attachments(),
        ];
    }

    /**
     * @return SiteRepresentation
     */
    public function page()
    {
        return $this->getAdapter('site_pages')
            ->getRepresentation($this->block->getPage());
    }

    /**
     * @return bool
     */
    public function layout()
    {
        return $this->block->getLayout();
    }

    /**
     * @return bool
     */
    public function data()
    {
        return $this->block->getData();
    }

    public function attachments()
    {
        $attachments = [];
        foreach ($this->block->getAttachments() as $attachment) {
            $attachments[]= new SiteBlockAttachmentRepresentation(
                $attachment, $this->getServiceLocator());
        }
        return $attachments;
    }
}
