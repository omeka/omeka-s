<?php
namespace EADImport\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class PropertyLoader extends AbstractHelper
{
    protected $selectorMarkup;

    public function __invoke($propertySelectorText = null, $active = true)
    {
        if ($this->selectorMarkup) {
            // Build the selector markup only once.
            return $this->selectorMarkup;
        }

        $vocabResponse = $this->getView()->api()->search('vocabularies');
        $propResponse = $this->getView()->api()->search('properties', ['limit' => 0]);

        return $this->getView()->partial(
            'ead-import/admin/mapping-sidebar/property-loader',
            [
                'vocabularies' => $vocabResponse->getContent(),
                'totalPropertyCount' => $propResponse->getTotalResults(),
                'propertySelectorText' => $propertySelectorText,
                'state' => $active ? 'always-open' : '',
            ]
        );
    }
}
