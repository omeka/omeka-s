<?php
namespace Omeka\Service;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class BlockLayoutManager implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const FORM_VIEW_NAME = 'block-layout/%s/form';
    const RENDER_VIEW_NAME = 'block-layout/%s/render';

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
        return sprintf(self::FORM_VIEW_NAME, $layout);
    }

    public function getRenderViewName($layout)
    {
        return sprintf(self::RENDER_VIEW_NAME, $layout);
    }
}
