<?php
namespace Omeka\Api\Representation;

use Omeka\Entity\SitePageBlock;
use Laminas\ServiceManager\ServiceLocatorInterface;

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

    public function jsonSerialize(): array
    {
        return [
            'o:layout' => $this->layout(),
            'o:data' => $this->data(),
            'o:layout_data' => $this->layoutData(),
            'o:attachment' => $this->attachments(),
        ];
    }

    /**
     * @return int
     */
    public function id()
    {
        return $this->block->getId();
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
     * @return string
     */
    public function layout()
    {
        return $this->block->getLayout();
    }

    /**
     * @return array
     */
    public function data()
    {
        return $this->block->getData();
    }

    /**
     * Get block data by key.
     *
     * @param string $key The data key
     * @param mixed $default Return this if key does not exist
     * @return mixed
     */
    public function dataValue($key, $default = null)
    {
        $data = $this->block->getData();
        return $data[$key] ?? $default;
    }

    /**
     * Get block layout data by key.
     *
     * @param string $key The layout data key
     * @param mixed $default Return this if key does not exist
     * @return mixed
     */
    public function layoutDataValue($key, $default = null)
    {
        $layoutData = $this->block->getLayoutData();
        return $layoutData[$key] ?? $default;
    }

    /**
     * @return array
     */
    public function layoutData()
    {
        return $this->block->getLayoutData();
    }

    public function attachments()
    {
        $attachments = [];
        foreach ($this->block->getAttachments() as $attachment) {
            $attachments[] = new SiteBlockAttachmentRepresentation(
                $attachment, $this->getServiceLocator());
        }
        return $attachments;
    }
}
