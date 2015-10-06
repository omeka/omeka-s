<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Entity\Site;
use Omeka\Api\Representation\SiteRepresentation;

class Fallback extends AbstractLink
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

    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return sprintf('%s [%s]', $translator->translate('Unknown'), $this->name);
    }

    public function getForm(array $data)
    {}

    public function toZend(array $data, Site $site)
    {
        return array(
            'type' => 'uri',
            'uri' => null,
            'label' => $data['label'],
        );
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return $data;
    }
}
