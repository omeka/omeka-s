<?php
namespace Omeka\DataType\Resource;

use Omeka\Entity;

class Item extends AbstractResource
{
    public function getName()
    {
        return 'resource:item';
    }

    public function getLabel()
    {
        return 'Item'; // @translate
    }

    public function getValidValueResources()
    {
        return [Entity\Item::class];
    }
}
