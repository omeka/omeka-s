<?php
namespace Omeka\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class PageController extends AbstractActionController
{
    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $query = $this->params()->fromQuery();
        $query['site_id'] = $this->currentSite()->id();

        $response = $this->api()->search('site_pages', $query);
        $this->paginator($response->getTotalResults());
        $pages = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('pages', $pages);
        return $view;
    }

    public function showAction()
    {
        $slug = $this->params('page-slug');
        $site = $this->currentSite();
        $page = $this->api()->read('site_pages', [
            'slug' => $slug,
            'site' => $site->id(),
        ])->getContent();

        $pageBodyClass = 'page site-page-' . preg_replace('([^a-zA-Z0-9\-])', '-', $slug);

        $this->viewHelpers()->get('sitePagePagination')->setPage($page);

        $view = new ViewModel;

        $view->setVariable('site', $site);
        $view->setVariable('page', $page);
        $view->setVariable('pageBodyClass', $pageBodyClass);
        $view->setVariable('displayNavigation', true);

        $contentView = clone $view;
        $contentView->setTemplate('omeka/site/page/content');
        $contentView->setVariable('pageViewModel', $view);

        $view->addChild($contentView, 'content');
        return $view;
    }
}
