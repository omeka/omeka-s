<?php
namespace EADImport\Mapping;

use Laminas\View\Renderer\PhpRenderer;

class PropertyMapping extends AbstractMapping
{
    protected $label = 'Properties'; // @translate
    protected $name = 'property-selector';

    public function getSidebar(PhpRenderer $view)
    {
        return $view->propertySelector($view->translate('Properties'), false);
    }
}
