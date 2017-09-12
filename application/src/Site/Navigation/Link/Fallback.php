<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\ErrorStore;

class Fallback implements LinkInterface
{
    /**
     * @var string The name of the unknown link
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return 'Fallback';
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/fallback';
    }

    public function getOriginalLabel()
    {
        return $this->name;
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        return true;
    }

    public function getLabel(array $data, SiteRepresentation $site)
    {
        return null;
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        return [
            'type' => 'uri',
            'uri' => null,
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return $data;
    }
}
