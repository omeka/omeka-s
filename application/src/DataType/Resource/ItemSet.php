<?php
namespace Omeka\DataType\Resource;

use Omeka\Entity;

class Itemset extends AbstractResource
{
    public function getName()
    {
        return 'resource:itemset';
    }

    public function getLabel()
    {
        return 'Item Set'; // @translate
    }

    public function getValidValueResources()
    {
        return [Entity\ItemSet::class];
    }
}
