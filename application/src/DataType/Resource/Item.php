<?php
namespace Omeka\DataType\Resource;

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
}
