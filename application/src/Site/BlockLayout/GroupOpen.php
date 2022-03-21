<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;

class GroupOpen extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Group open'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $form = new Form;
        $class = new Element\Text("o:block[__blockIndex__][o:data][class]");
        $class->setOptions([
            'label' => 'Class', // @translate
            'info' => 'Optional CSS class for this group.', // @translate
        ]);
        if ($block) {
            $class->setValue($block->dataValue('class'));
        }
        $form->add($class);
        return $view->formCollection($form);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $class = $block->dataValue('class');
        return sprintf('<div class="%s">', $class ? $view->escapeHtml($class) : '');
    }
}
