<?php
namespace Omeka\Controller\Site;

use Omeka\Api\Exception\NotFoundException;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $site = $this->currentSite();

        // Redirect to the configured default homepage, if it exists.
        $homepageId = $this->siteSettings()->get('default_homepage_id');
        if ($homepageId) {
            try {
                $homepage = $this->api()->read('site_pages', $homepageId)->getContent();
                return $this->redirect()->toRoute('site/page', [
                    'site-slug' => $site->slug(),
                    'page-slug' => $homepage->slug(),
                ]);
            } catch (NotFoundException $e) {
                // The page was not found.
            }
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
}
