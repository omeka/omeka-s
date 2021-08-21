<?php
namespace Omeka\DataType\Resource;

use Omeka\DataType\ValueAnnotatableInterface;
use Omeka\Entity;
use Laminas\View\Renderer\PhpRenderer;

class Itemset extends AbstractResource implements ValueAnnotatableInterface
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
        return $view->partial('common/data-type/value-annotation-resource', ['dataTypeName' => 'resource:itemsets']);
    }

    public function getValidValueResources()
    {
        return [Entity\ItemSet::class];
    }
}
