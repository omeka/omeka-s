<?php
namespace Omeka\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Hidden;
use Zend\Form\Element\Textarea;
use Zend\View\Renderer\PhpRenderer;

class OneItem extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'One Item';
    }

    public function prepareForm(PhpRenderer $view)
    {}

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {}

    public function form(PhpRenderer $view, SitePageBlockRepresentation $block = null)
    {
        return $this->attachmentForms($view, 1, $block);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {}
}
