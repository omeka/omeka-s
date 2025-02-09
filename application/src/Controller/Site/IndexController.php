<?php
namespace Omeka\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

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

        $siteSettings = $this->siteSettings();

        $resourceNames = $siteSettings->get('search_resource_names', ['site_pages', 'items']);

        // Skip the intermediate result page when only one resource type is set.
        if (count($resourceNames) === 1) {
            $resourceName = reset($resourceNames);
            $resourceControllers = [
                'site_pages' => 'page',
                'items' => 'item',
                'item_sets' => 'item-set',
            ];
            return $this->redirect()->toRoute(
                $resourceName === 'site_pages' ? 'site/page-browse' : 'site/resource',
                ['controller' => $resourceControllers[$resourceName], 'action' => 'browse'],
                ['query' => ['fulltext_search' => $fulltextQuery]],
                true
            );
        }

        $query = [
            'fulltext_search' => $fulltextQuery,
            'site_id' => $this->currentSite()->id(),
            'limit' => 10,
        ];

        // This settings is managed only by items and media, else skipped.
        if ($siteSettings->get('browse_attached_items', false)) {
            $query['site_attachments_only'] = true;
        }

        $results = [];
        foreach ($resourceNames as $resourceName) {
            $response = $this->api()->search($resourceName, $query);
            $totalResults = $response->getTotalResults();
            if (!$totalResults) {
                continue;
            }
            $results[$resourceName] = [
                'resources' => $response->getContent(),
                'total' => $totalResults,
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
