<?php
namespace Omeka\Thumbnail;

use Omeka\Thumbnail\ThumbnailerInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class GdThumbnailer implements ThumbnailerInterface
{
    use ServiceLocatorAwareTrait;
}
