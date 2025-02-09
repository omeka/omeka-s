<?php
namespace Omeka\DataType\Resource;

use Omeka\Entity;

class All extends AbstractResource
{
    public function getName()
    {
        return 'resource';
    }

    public function getLabel()
    {
        return 'All'; // @translate
    }

    public function getValidValueResources()
    {
        return [Entity\Item::class, Entity\ItemSet::class, Entity\Media::class];
    }
}
