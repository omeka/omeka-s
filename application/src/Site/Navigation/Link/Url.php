<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Entity\Site;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\ErrorStore;

class Url extends AbstractLink
{
    public function getLabel()
    {
        return 'Custom URL';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        if (!isset($data['label'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: URL link missing label');
            return false;
        }
        if (!isset($data['url'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: URL link missing URL');
            return false;
        }
        return true;
    }

    public function getForm(array $data, SiteRepresentation $site)
    {
        $escape = $this->getViewHelper('escapeHtml');
        $label = isset($data['label']) ? $data['label'] : $this->getLabel();
        $url = isset($data['url']) ? $data['url'] : null;
        return '<label>Type <input type="text" value="' . $escape($this->getLabel()) . '" disabled></label>'
            . '<label>Label <input type="text" data-name="label" value="' . $escape($label) . '"></label>'
            . '<label>URL <input type="text" data-name="url" value="' . $escape($url) . '"></label>';
    }

    public function toZend(array $data, Site $site)
    {
        return [
            'type' => 'uri',
            'uri' => $data['url'],
            'label' => $data['label'],
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return [
            'label' => $data['label'],
            'url' => $data['url'],
        ];
    }
}
