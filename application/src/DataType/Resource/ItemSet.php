<?php
namespace Omeka\DataType\Resource;

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
}
