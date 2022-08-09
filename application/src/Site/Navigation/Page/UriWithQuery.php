<?php
namespace Omeka\Site\Navigation\Page;

use Laminas\Http\Request;
use Laminas\Navigation\Page\AbstractPage;
use Laminas\Navigation\Page\Uri;
use Laminas\Uri\UriFactory;

class UriWithQuery extends Uri
{
    public function isActive($recursive = false)
    {
        if (!$this->active) {
            if ($this->getRequest() instanceof Request) {
                $uriCurrent = $this->getRequest()->getUri();
                $uriCurrentQuery = $uriCurrent->getQueryAsArray();
                unset($uriCurrentQuery['page'], $uriCurrentQuery['sort_by'], $uriCurrentQuery['sort_order']);
                $uriCurrent->setQuery($uriCurrentQuery);

                $uriPage = UriFactory::factory($this->getUri());
                $uriPageQuery = $uriPage->getQueryAsArray();
                unset($uriPageQuery['page'], $uriPageQuery['sort_by'], $uriPageQuery['sort_order']);
                $uriPage->setQuery($uriPageQuery);

                $identicalPaths = $uriCurrent->getPath() === $uriPage->getPath();
                $identicalQueries = $uriCurrent->getQuery() === $uriPage->getQuery();

                if ($identicalPaths && $identicalQueries) {
                    $this->active = true;
                    return true;
                }
            }
        }
        return AbstractPage::isActive($recursive);
    }
}
