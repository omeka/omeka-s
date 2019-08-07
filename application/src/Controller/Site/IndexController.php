<?php
namespace Omeka\Controller\Site;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $site = $this->currentSite();

        // Redirect to the configured homepage, if it exists.
        $homepage = $site->homepage();
        if ($homepage) {
            return $this->redirect()->toRoute('site/page', [
                'site-slug' => $site->slug(),
                'page-slug' => $homepage->slug(),
            ]);
        }

        // Redirect to the first linked page, if it exists.
        $linkedPages = $site->linkedPages();
        if ($linkedPages) {
            $firstPage = current($linkedPages);
            return $this->redirect()->toRoute('site/page', [
                'site-slug' => $site->slug(),
                'page-slug' => $firstPage->slug(),
            ]);
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        return $view;
    }

    public function searchAction()
    {
        $fulltextQuery = $this->params()->fromQuery('fulltext_search');
        $query = [
            'fulltext_search' => $fulltextQuery,
            'site_id' => $this->currentSite()->id(),
            'limit' => 10,
        ];

        // Get page results.
        $pagesResponse = $this->api()->search('site_pages', $query);
        $pages = $pagesResponse->getContent();

        // Get item results.
        if ($this->siteSettings()->get('browse_attached_items', false)) {
            $query['site_attachments_only'] = true;
        }
        $itemsResponse = $this->api()->search('items', $query);
        $items = $itemsResponse->getContent();

        $view = new ViewModel;
        $view->setVariable('query', $fulltextQuery);
        $view->setVariable('pages', $pages);
        $view->setVariable('pagesTotal', $pagesResponse->getTotalResults());
        $view->setVariable('items', $items);
        $view->setVariable('itemsTotal', $itemsResponse->getTotalResults());
        return $view;
    }
}
