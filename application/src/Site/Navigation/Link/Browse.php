<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Entity\Site;
use Omeka\Api\Representation\SiteRepresentation;

class Browse extends AbstractLink
{
    public function getLabel()
    {
        return 'Browse';
    }

    public function getForm(array $data)
    {
        $label = isset($data['label']) ? $data['label'] : $this->getLabel();
        $query = isset($data['query']) ? $data['query'] : null;
        return '<label>Label <input type="text" data-name="label" value="' . $label . '"></label>'
            . '<label>Query <input type="text" data-name="query" value="' . $query . '"></label>';
    }

    public function toZend(array $data, Site $site)
    {
        parse_str($data['query'], $query);
        return array(
            'label' => $data['label'],
            'route' => 'site/resource',
            'params' => array(
                'site-slug' => $site->getSlug(),
                'controller' => 'item',
                'action' => 'browse',
            ),
            'query' => $query,
        );
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        $label = isset($data['label']) ? $data['label'] : $sitePage->title();
        $query = isset($data['query']) ? $data['query'] : null;
        return array(
            'text' => $label,
            'data' => array(
                'type' => 'browse',
                'label' => $label,
                'query' => $query,
            ),
        );
    }
}
