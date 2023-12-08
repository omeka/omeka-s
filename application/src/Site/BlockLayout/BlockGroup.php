<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;

class BlockGroup extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Block group'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $form = new Form;

        $elementSpan = new Element\Number("o:block[__blockIndex__][o:data][span]");
        $elementSpan->setOptions([
                'label' => 'Block span', // @translate
                'info' => 'Number of blocks to include in this group. Groups may not overlap.', // @translate
            ])
            ->setAttribute('min', '1')
            ->setValue($block ? $block->dataValue('span') : '1');
        $form->add($elementSpan);

        $elementClass = new Element\Text("o:block[__blockIndex__][o:data][class]");
        $elementClass->setOptions([
                'label' => 'Class', // @translate
                'info' => 'Optional CSS class for this group.', // @translate
            ])
            ->setAttributes([
                'class' => 'block-group-span',
            ])
            ->setValue($block ? $block->dataValue('class') : '');
        $form->add($elementClass);

        return $view->formCollection($form);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return '';
    }
}
