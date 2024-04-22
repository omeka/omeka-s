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
            ->setValue($block ? $block->dataValue('span') : '0');
        $form->add($elementSpan);
        $dropZoneString = $view->translate('Drag blocks here to group.');

        return <<<END
            {$view->formCollection($form, false)}
            <div class="block-group-blocks"><span class="empty-drop-zone">{$dropZoneString}</span></div>
        END;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return '';
    }
}
