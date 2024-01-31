<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Laminas\Form;
use Laminas\View\Renderer\PhpRenderer;

class PageDateTime extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    protected $defaultData = [
        'display' => 'created_modified',
        'date_format' => 'medium',
        'time_format' => 'none',
    ];

    public function getLabel()
    {
        return 'Page date/time'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null)
    {
        $form = new Form\Form;
        $form->add([
            'type' => Form\Element\Radio::class,
            'name' => 'o:block[__blockIndex__][o:data][display]',
            'options' => [
                'label' => 'Display date/time', // @translate
                'value_options' => [
                    'created_modified' => 'Created & modified', // @translate
                    'created' => 'Created', // @translate
                    'modified' => 'Modified', // @translate
                ],
            ],
        ]);
        $form->add([
            'type' => Form\Element\Radio::class,
            'name' => 'o:block[__blockIndex__][o:data][date_format]',
            'options' => [
                'label' => 'Date format', // @translate
                'value_options' => [
                    'none' => 'None', // @translate
                    'short' => 'Short', // @translate
                    'medium' => 'Medium', // @translate
                    'long' => 'Long', // @translate
                    'full' => 'Full', // @translate
                ],
            ],
        ]);
        $form->add([
            'type' => Form\Element\Radio::class,
            'name' => 'o:block[__blockIndex__][o:data][time_format]',
            'options' => [
                'label' => 'Time format', // @translate
                'value_options' => [
                    'none' => 'None', // @translate
                    'short' => 'Short', // @translate
                    'medium' => 'Medium', // @translate
                    'long' => 'Long', // @translate
                    'full' => 'Full', // @translate
                ],
            ],
        ]);
        $data = $block ? $block->data() + $this->defaultData : $this->defaultData;
        $form->setData([
            'o:block[__blockIndex__][o:data][display]' => $data['display'],
            'o:block[__blockIndex__][o:data][date_format]' => $data['date_format'],
            'o:block[__blockIndex__][o:data][time_format]' => $data['time_format'],
        ]);
        return $view->formCollection($form, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = 'common/block-layout/page-date-time')
    {
        return $view->partial($templateViewScript, [
            'block' => $block,
        ]);
    }
}
