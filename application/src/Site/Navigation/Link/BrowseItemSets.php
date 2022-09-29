<?php
namespace Omeka\Site\Navigation\Link;

use Laminas\View\HelperPluginManager;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\Navigation\Page\UriWithQuery;
use Omeka\Stdlib\ErrorStore;

class BrowseItemSets implements LinkInterface
{
    protected $viewHelperManager;

    public function __construct(HelperPluginManager $viewHelperManager)
    {
        $this->viewHelperManager = $viewHelperManager;
    }

    public function getName()
    {
        return 'Browse item sets'; // @translate
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/browse';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        return true;
    }

    public function getLabel(array $data, SiteRepresentation $site)
    {
        return isset($data['label']) && '' !== trim($data['label'])
            ? $data['label'] : null;
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        $urlHelper = $this->viewHelperManager->get('url');
        return [
            'type' => UriWithQuery::class,
            'uri' => $urlHelper(
                'site/resource',
                ['site-slug' => $site->slug(), 'controller' => 'item-set', 'action' => 'browse'],
                ['query' => $data['query']],
            ),

        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return [
            'label' => $data['label'],
            'query' => $data['query'],
        ];
    }
}
