<?php
namespace Omeka\DataType\Resource;

use Omeka\DataType\ValueAnnotatingInterface;
use Omeka\Entity;
use Laminas\View\Renderer\PhpRenderer;

class Item extends AbstractResource implements ValueAnnotatingInterface
{
    public function getName()
    {
        return 'resource:item';
    }

    public function getLabel()
    {
        return 'Item'; // @translate
    }

    public function valueAnnotationPrepareForm(PhpRenderer $view)
    {
    }

    public function valueAnnotationForm(PhpRenderer $view)
    {
        return $view->partial('common/data-type/value-annotation-resource', [
            'dataTypeLabel' => $view->translate('Items'),
            'dataTypeSingle' => 'item',
            'dataTypePlural' => 'items',
        ]);
    }

    public function getValidValueResources()
    {
        return [Entity\Item::class];
    }
}
