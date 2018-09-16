<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\Form\Element;
use Zend\Form\Form;
use Zend\View\Renderer\PhpRenderer;

class ListOfSites extends AbstractBlockLayout
{
    protected $defaults = [
        'limit' => null,
    ];

    public function getLabel()
    {
        return 'List of sites'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $data = $block ? $block->data() + $this->defaults : $this->defaults;

        $form = new Form();
        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][limit]',
            'type' => Element\Number::class,
            'options' => [
                'label' => 'Max number of sites', // @translate
                'info' => 'An empty value means no limit.', // @translate
            ],
            'attributes' => [
                'id' => 'list-of-sites-limit',
                'placeholder' => '100', // @translate
            ],
        ]);

        $form->setData([
            'o:block[__blockIndex__][o:data][limit]' => $data['limit'],
        ]);

        return $view->formCollection($form);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $limit = $block->dataValue('limit', $this->defaults['limit']);

        $data = [];
        if ($limit) {
            $data['limit'] = $limit;
        }

        $response = $view->api()->search('sites', $data);
        $sites = $response->getContent();

        return $view->partial('common/block-layout/list-of-sites', [
            'sites' => $sites,
        ]);
    }
}
