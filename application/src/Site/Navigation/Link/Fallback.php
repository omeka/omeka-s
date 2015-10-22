<?php
namespace Omeka\Site\Navigation\Link;

use Omeka\Entity\Site;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\ErrorStore;

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

    public function isValid(array $data, ErrorStore $errorStore)
    {
        return true;
    }

    public function getForm(array $data)
    {
        $escape = $this->getViewHelper('escapeHtml');
        return '<label>Type <input type="text" value="' . $escape($this->getLabel()) . '" disabled></label>'
            . '<label>Data <textarea disabled>' . $escape(json_encode($data)) . '</textarea></label>';
    }

    public function toZend(array $data, Site $site)
    {
        return [
            'type' => 'uri',
            'uri' => null,
            'label' => $data['label'],
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return $data;
    }
}
