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

    public function getForm(array $data)
    {
        $label = isset($data['label']) ? $data['label'] : $this->getLabel();
        $url = isset($data['url']) ? $data['url'] : null;
        return '<label>Label <input type="text" data-name="label" value="' . $label . '"></label>'
            . '<label>URL <input type="text" data-name="url" value="' . $url . '"></label>';
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
            'label' => $data['label'],
            'url' => $data['url'],
        );
    }
}
