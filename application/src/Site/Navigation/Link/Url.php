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

    /**
     * Get the data needed for a newly created link form.
     *
     * @return array
     */
    public function getData()
    {
        return array();
    }

    /**
     * Populate the link form using the passed data.
     *
     * @param array $data
     * @return string
     */
    public function getForm(array $data)
    {
        $url = isset($data['url']) ? $data['url'] : null;
        return '<label>Label <input type="text" value="' . $data['label'] . '"></label>'
            . '<label>URL <input type="text" value="' . $url . '"></label>';
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
