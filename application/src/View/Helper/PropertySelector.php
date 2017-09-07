<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering the property selector.
 */
class PropertySelector extends AbstractHelper
{
    /**
     * @var string Selector markup cache
     */
    protected $selectorMarkup;

    /**
     * Return the property selector form control.
     *
     * @param string $propertySelectorText
     * @param bool $active
     * @return string
     */
    public function __invoke($propertySelectorText = null, $active = true)
    {
        if ($this->selectorMarkup) {
            // Build the selector markup only once.
            return $this->selectorMarkup;
        }

        $vocabResponse = $this->getView()->api()->search('vocabularies');
        $propResponse = $this->getView()->api()->search('properties', ['limit' => 0]);

        return $this->getView()->partial(
            'common/property-selector',
            [
                'vocabularies' => $vocabResponse->getContent(),
                'totalPropertyCount' => $propResponse->getTotalResults(),
                'propertySelectorText' => $propertySelectorText,
                'state' => $active ? 'always-open' : '',
            ]
        );
    }
}
