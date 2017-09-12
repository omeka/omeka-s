<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\ErrorStore;

class Url implements LinkInterface
{
    public function getName()
    {
        return 'Custom URL'; // @translate
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/url';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        if (!isset($data['label']) || '' === trim($data['label'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: URL link missing label');
            return false;
        }
        if (!isset($data['url']) || '' === trim($data['url'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: URL link missing URL');
            return false;
        }
        return true;
    }

    public function getLabel(array $data, SiteRepresentation $site)
    {
        return isset($data['label']) && '' !== trim($data['label'])
            ? $data['label'] : null;
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        return [
            'type' => 'uri',
            'uri' => $data['url'],
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
