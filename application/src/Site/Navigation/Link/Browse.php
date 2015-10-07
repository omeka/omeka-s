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
        $escape = $this->getViewHelper('escapeHtml');
        $label = isset($data['label']) ? $data['label'] : $this->getLabel();
        $query = isset($data['query']) ? $data['query'] : null;
        return '<label>Type <input type="text" value="' . $escape($this->getLabel()) . '" disabled></label>'
            . '<label>Label <input type="text" data-name="label" value="' . $label . '"></label>'
            . '<label>Query <input type="text" data-name="query" value="' . $query . '"></label>';
    }

    public function toZend(array $data, Site $site)
    {
        parse_str($data['query'], $query);
        return [
            'label' => $data['label'],
            'route' => 'site/resource',
            'params' => [
                'site-slug' => $site->getSlug(),
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
