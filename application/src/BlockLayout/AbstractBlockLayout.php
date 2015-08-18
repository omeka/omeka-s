<?php
namespace Omeka\BlockLayout;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

abstract class AbstractBlockLayout implements BlockLayoutInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function prepareForm(PhpRenderer $view)
    {}

    /**
     * {@inheritDoc}
     */
    public function prepareRender(PhpRenderer $view)
    {}

    /**
     * {@inheritDoc}
     */
    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {}
}
