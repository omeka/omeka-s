<?php
namespace Omeka\DataType\Resource;

use Omeka\Entity;

class Media extends AbstractResource
{
    public function getName()
    {
        return 'resource:media';
    }

    public function getLabel()
    {
        return 'Media'; // @translate
    }

    public function getValidValueResources()
    {
        return [Entity\Media::class];
    }
}
