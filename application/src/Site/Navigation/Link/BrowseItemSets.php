<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\ErrorStore;

class BrowseItemSets implements LinkInterface
{
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
        parse_str($data['query'], $query);
        return [
            'route' => 'site/resource',
            'params' => [
                'site-slug' => $site->slug(),
                'controller' => 'item-set',
                'action' => 'browse',
            ],
            'query' => $query,
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
