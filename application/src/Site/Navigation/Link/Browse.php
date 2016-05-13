<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\ErrorStore;

class Browse implements LinkInterface
{
    public function getLabel()
    {
        return 'Browse'; // @translate
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/browse';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        if (!isset($data['label'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: browse link missing label');
            return false;
        }
        if (!isset($data['query'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: browse link missing query');
            return false;
        }
        return true;
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        parse_str($data['query'], $query);
        return [
            'label' => $data['label'],
            'route' => 'site/resource',
            'params' => [
                'site-slug' => $site->slug(),
                'controller' => 'item',
                'action' => 'browse',
            ],
            'query' => $query,
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        $label = isset($data['label']) ? $data['label'] : $sitePage->title();
        $query = isset($data['query']) ? $data['query'] : null;
        return [
            'label' => $label,
            'query' => $query,
        ];
    }
}
