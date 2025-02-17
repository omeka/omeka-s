<?php
namespace EADImport\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class MappingLoader extends AbstractHelper
{
    public function __invoke()
    {
        $mappingModels = $this->getView()->api()->search('eadimport_mapping_models')->getContent();

        return $this->getView()->partial(
            'ead-import/admin/mapping-sidebar/mapping-loader',
            [
                'mappingModels' => $mappingModels,
            ]
        );
    }
}
