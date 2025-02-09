<?php
namespace Omeka\File\Thumbnailer;

use Omeka\File\Exception;

class NoThumbnail extends AbstractThumbnailer
{
    public function create($strategy, $constraint, array $options = [])
    {
        throw new Exception\CannotCreateThumbnailException;
    }
}
