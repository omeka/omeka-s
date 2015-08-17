<?php
namespace Omeka\Block;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Manager implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const FORM_VIEW_NAME = 'block/%s/form';
    const RENDER_VIEW_NAME = 'block/%s/render';

    protected $layouts;

    public function __construct(array $layouts)
    {
        $this->layouts = $layouts;
    }

    public function getLayouts()
    {
        return $this->layouts;
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
