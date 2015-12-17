<?php
namespace Omeka\Controller\Site;

use Zend\View\Model\ViewModel;

class PageController extends AbstractSiteController
{
    public function showAction()
    {
        $site = $this->getSite();
        $siteId = $site->id();

        $pageResponse = $this->api()->read('site_pages', [
            'slug' => $this->params('page-slug'),
            'site' => $siteId
        ]);
        $page = $pageResponse->getContent();

        $this->getServiceLocator()->get('ViewHelperManager')
            ->get('sitePagePagination')->setPage($page);

        $view = new ViewModel;

        $view->setVariable('site', $site);
        $view->setVariable('page', $page);
        return $view;
    }
}
