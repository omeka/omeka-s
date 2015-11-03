<?php
namespace Omeka\Controller\Site;

use Zend\Mvc\Controller\AbstractActionController;

abstract class AbstractSiteController extends AbstractActionController
{
    protected function getSite()
    {
        return $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ])->getContent();
    }
}
