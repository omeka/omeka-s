<?php
namespace Omeka\DataType\Resource;

class All extends AbstractResource
{
    public function getName()
    {
        return 'resource:all';
    }

    public function getLabel()
    {
        return 'All'; // @translate
    }
}
