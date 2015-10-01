<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Entity\Site;
use Omeka\Api\Representation\SiteRepresentation;

class Page extends AbstractLink
{
    public function getLabel()
    {
        return 'Page';
    }

    public function getForm($data)
    {
        return '<label>Label <input type="text" value="' . $data['label'] . '"></label>';
    }

    public function toZend(array $data, Site $site)
    {
        $sitePage = $site->getPages()->get($data['id']);
        if ($sitePage) {
            return array(
                'label' => $sitePage->getTitle(),
                'route' => 'site/page',
                'params' => array(
                    'site-slug' => $site->getSlug(),
                    'page-slug' => $sitePage->getSlug(),
                ),
            );
        }
        return array(
            'type' => 'uri',
            'label' => '[invalid page]',
        );
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        $sitePage = $site->pages()[$data['id']];
        return array(
            'text' => $sitePage->title(),
            'data' => array(
                'type' => 'page',
                'id' => $sitePage->id(),
                'label' => $data['label'],
            ),
        );
    }
}
