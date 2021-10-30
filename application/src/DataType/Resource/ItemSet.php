<?php
namespace Omeka\DataType\Resource;

use Omeka\DataType\ValueAnnotatingInterface;
use Omeka\Entity;
use Laminas\View\Renderer\PhpRenderer;

class Itemset extends AbstractResource implements ValueAnnotatingInterface
{
    public function getName()
    {
        return 'resource:itemset';
    }

    public function getLabel()
    {
        return 'Item Set'; // @translate
    }

    public function valueAnnotationPrepareForm(PhpRenderer $view)
    {
    }

    public function valueAnnotationForm(PhpRenderer $view)
    {
        return $view->partial('common/data-type/value-annotation-resource', [
            'dataTypeLabel' => $view->translate('Item Sets'),
            'dataTypeSingle' => 'item-set',
            'dataTypePlural' => 'item-sets',
        ]);
    }

    public function getValidValueResources()
    {
        return [Entity\ItemSet::class];
    }
}
