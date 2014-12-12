<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PropertySelector extends AbstractHelper
{
    public function __invoke()
    {
        $response = $this->getView()->api()->search('vocabularies');
        
        if ($response->isError()) {
            return;
        }
        return $this->getView()->partial(
            'common/property-selector',
            array(
                'vocabularies' => $response->getContent(),
            )
        );
    }
}
