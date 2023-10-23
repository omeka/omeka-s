<?php
namespace Omeka\Site\Navigation\Page;

use Laminas\Navigation\Page\Uri;

class UriTargetBlank extends Uri
{
    public function getTarget()
    {
        return '_blank';
    }
}
