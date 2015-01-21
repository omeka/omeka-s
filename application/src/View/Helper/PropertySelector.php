<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PropertySelector extends AbstractHelper
{
    /**
     * @var string Selector markup cache
     */
    protected $selectorMarkup;

    /**
     * Return the property selector form control.
     *
     * @return string
     */
    public function __invoke()
    {
        if ($this->selectorMarkup) {
            // Build the selector markup only once.
            return $this->selectorMarkup;
        }

        $response = $this->getView()->api()->search('vocabularies');
        if ($response->isError()) {
            return;
        }

        return $this->getView()->partial(
            'common/property-selector',
            array('vocabularies' => $response->getContent())
        );
    }
}
