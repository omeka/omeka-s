<?php
namespace Omeka\Site\Navigation\Page;

use Laminas\Http\Request;
use Laminas\Navigation\Page\AbstractPage;
use Laminas\Navigation\Page\Uri;
use Laminas\Uri\UriFactory;

class UriTargetBlank extends Uri
{
    public function getTarget()
    {
        return '_blank';
    }
}
