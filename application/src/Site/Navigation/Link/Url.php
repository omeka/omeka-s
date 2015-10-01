<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Entity\Site;
use Omeka\Api\Representation\SiteRepresentation;

class Url extends AbstractLink
{
    public function getLabel()
    {
        return 'Custom URL';
    }

    public function getTemplate()
    {
        return '<label>Label<input type="text" value="__label__"></label>';
    }

    public function toZend(array $data, Site $site)
    {
        return array(
            'type' => 'uri',
            'uri' => $data['url'],
            'label' => $data['label'],
        );
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return array(
            'text' => $data['label'],
            'data' => array(
                'type' => 'url',
                'label' => $data['label'],
                //'url' => $data['url'],
            ),
        );
    }
}
