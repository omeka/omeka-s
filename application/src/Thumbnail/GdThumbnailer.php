<?php
namespace Omeka\Thumbnail;

use Omeka\Thumbnail\ThumbnailerInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class GdThumbnailer implements ThumbnailerInterface
{
    use ServiceLocatorAwareTrait;

    public function create($source, $constraint)
    {}

    public function createSquare($source, $constraint)
    {}
}
