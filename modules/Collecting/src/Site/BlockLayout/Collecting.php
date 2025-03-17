<?php
namespace Collecting\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Laminas\Form\Element;
use Laminas\View\Renderer\PhpRenderer;

class Collecting extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Collecting'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null)
    {
        $forms = $view->api()
            ->search('collecting_forms', ['site_id' => $site->id()])
            ->getContent();
        $formCheckboxes = new Element\MultiCheckbox('o:block[__blockIndex__][o:data][forms]');
        $valueOptions = [];
        foreach ($forms as $form) {
            $valueOptions[$form->id()] = $form->label();
        }
        $formCheckboxes->setValueOptions($valueOptions);
        if ($block) {
            $formCheckboxes->setValue($block->dataValue('forms'));
        }
        return $view->partial('common/block-layout/collecting-block-form', [
            'formCheckboxes' => $formCheckboxes,
        ]);
    }

    public function prepareRender(PhpRenderer $view)
    {
        $view->collectingPrepareForm();
        $view->headLink()->appendStylesheet($view->assetUrl('css/collecting.css', 'Collecting'));
        $view->headScript()->appendFile($view->assetUrl('js/collecting-block.js', 'Collecting'));
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $cForms = [];
        foreach ($block->dataValue('forms', []) as $formId) {
            try {
                $cForms[] = $view->api()->read('collecting_forms', $formId)->getContent();
            } catch (NotFoundException $e) {
                // The form was likely deleted since it was added to this block.
            }
        }
        if (1 === count($cForms)) {
            return $view->partial('common/block-layout/collecting-block-one', [
                'cForm' => $cForms[0],
            ]);
        } elseif (1 < count($cForms)) {
            return $view->partial('common/block-layout/collecting-block-multiple', [
                'cForms' => $cForms,
            ]);
        }
    }
}
