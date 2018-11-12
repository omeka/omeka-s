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
        'sort' => 'alpha',
        'limit' => null,
        'pagination' => false,
        'summaries' => true,
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
            'name' => 'o:block[__blockIndex__][o:data][sort]',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Sort', // @translate
                'value_options' => [
                    'alpha' => 'Alphabetical', // @translate
                    'oldest' => 'Oldest first', // @translate
                    'newest' => 'Newest first', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'list-of-sites-sort',
            ],
        ]);
        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][limit]',
            'type' => Element\Number::class,
            'options' => [
                'label' => 'Max number of sites', // @translate
                'info' => 'An empty value means no limit.', // @translate
            ],
            'attributes' => [
                'id' => 'list-of-sites-limit',
                'placeholder' => 'Unlimited', // @translate
                'min' => 0,
            ],
        ]);
        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][pagination]',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Pagination', // @translate
                'info' => 'Show pagination (only if a limit is set)', // @translate
            ],
            'attributes' => [
                'id' => 'list-of-sites-pagination',
            ],
        ]);
        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][summaries]',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Show summaries', // @translate
            ],
            'attributes' => [
                'id' => 'list-of-sites-summaries',
            ],
        ]);

        $form->setData([
            'o:block[__blockIndex__][o:data][sort]' => $data['sort'],
            'o:block[__blockIndex__][o:data][limit]' => $data['limit'],
            'o:block[__blockIndex__][o:data][pagination]' => $data['pagination'],
            'o:block[__blockIndex__][o:data][summaries]' => $data['summaries'],
        ]);

        return $view->formCollection($form, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $sort = $block->dataValue('sort', $this->defaults['sort']);
        $limit = $block->dataValue('limit', $this->defaults['limit']);
        $pagination = $limit && $block->dataValue('pagination', $this->defaults['pagination']);
        $summaries = $block->dataValue('summaries', $this->defaults['summaries']);

        $data = [];
        if ($pagination) {
            $currentPage = $view->params()->fromQuery('page', 1);
            $data['page'] = $currentPage;
            $data['per_page'] = $limit;
        } elseif ($limit) {
            $data['limit'] = $limit;
        }

        switch ($sort) {
            case 'oldest':
                $data['sort_by'] = 'created';
                break;
            case 'newest':
                $data['sort_by'] = 'created';
                $data['sort_order'] = 'desc';
                break;
            default:
            case 'alpha':
                $data['sort_by'] = 'title';
                break;
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
            'summaries' => $summaries,
        ]);
    }
}
