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

        $elementSpan = new Element\Hidden("o:block[__blockIndex__][o:data][span]");
        $elementSpan->setAttribute('class', 'block-group-span')
            ->setValue($block ? $block->dataValue('span') : '1');
        $form->add($elementSpan);

        return <<<END
            {$view->formCollection($form, false)}
            <div class="block-group-blocks" style="border-left: 8px solid #e0e0e0; padding: 2px; min-height: 40px;"></div>
        END;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return '';
    }
}
