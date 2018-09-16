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
        'pagination' => false,
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
        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][pagination]',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Add pagination in case of a limit', // @translate
            ],
            'attributes' => [
                'id' => 'list-of-sites-pagination',
            ],
        ]);

        $form->setData([
            'o:block[__blockIndex__][o:data][limit]' => $data['limit'],
            'o:block[__blockIndex__][o:data][pagination]' => $data['pagination'],
        ]);

        return $view->formCollection($form);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $limit = $block->dataValue('limit', $this->defaults['limit']);
        $pagination = $limit && $block->dataValue('pagination', $this->defaults['pagination']);

        $data = [];
        if ($pagination) {
            $currentPage = $view->params()->fromQuery('page', 1);
            $data['page'] = $currentPage;
            $data['per_page'] = $limit;
        } elseif ($limit) {
            $data['limit'] = $limit;
        }

        $response = $view->api()->search('sites', $data);

        if ($pagination) {
            $totalCount = $response->getTotalResults();
            $view->pagination(null, $totalCount, $currentPage, $limit);
        }

        $sites = $response->getContent();

        return $view->partial('common/block-layout/list-of-sites', [
            'sites' => $sites,
            'pagination' => $pagination,
        ]);
    }
}
