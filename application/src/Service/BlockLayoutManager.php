<?php
namespace Omeka\Service;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class BlockLayoutManager implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    protected $blockLayouts;

    public function __construct(array $blockLayouts)
    {
        $this->blockLayouts = $blockLayouts;
    }

    public function getBlockLayouts()
    {
        return $this->blockLayouts;
    }

    public function getFormViewName($layout)
    {
        return sprintf('block-layout/%s/form', $layout);
    }

    public function getRenderViewName($layout)
    {
        return sprintf('block-layout/%s/render', $layout);
    }
}
