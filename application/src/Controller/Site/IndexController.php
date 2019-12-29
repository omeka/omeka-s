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

        $siteSettings = $this->siteSettings();

        // This settings is managed only by items and media, else skipped.
        if ($siteSettings->get('browse_attached_items', false)) {
            $query['site_attachments_only'] = true;
        }

        $resourceNames = $siteSettings->get('search_resource_names', ['site_pages', 'items', 'item_sets', 'media']);

        $results = [];
        foreach ($resourceNames as $resourceName) {
            $response = $this->api()->search($resourceName, $query);
            $results[$resourceName] = [
                'resources' => $response->getContent(),
                'total' => $response->getTotalResults(),
            ];
        }

        $view = new ViewModel;
        $view
            ->setVariable('query', $fulltextQuery)
            ->setVariable('results', $results)
            // Kept for compatibility with old themes.
            ->setVariable('pages', @$results['site_pages']['resources'])
            ->setVariable('pagesTotal', @$results['site_pages']['total'])
            ->setVariable('items', @$results['items']['resources'])
            ->setVariable('itemsTotal', @$results['site_pages']['total']);
        return $view;
    }
}
