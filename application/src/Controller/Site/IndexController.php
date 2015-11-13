<?php
namespace Omeka\Controller\Site;

use Zend\View\Model\ViewModel;

class IndexController extends AbstractSiteController
{
    public function indexAction()
    {
        $site = $this->getSite();

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
