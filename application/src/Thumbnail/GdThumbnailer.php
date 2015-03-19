<?php
namespace Omeka\Thumbnail;

use Omeka\Thumbnail\ThumbnailerInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class GdThumbnailer implements ThumbnailerInterface
{
    use ServiceLocatorAwareTrait;

    protected $source;

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function create($type, $constraint)
    {}
}
