<?php
namespace Omeka\Controller\Site;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $site = $this->getSite();

        // Redirect to the first page, if it exists
        $pages = $site->pages();
        if ($pages) {
            $firstPage = current($pages);
            return $this->redirect()->toRoute('site/page', [
                'site-slug' => $site->slug(),
                'page-slug' => $firstPage->slug(),
            ]);
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        return $view;
    }

    protected function getSite()
    {
        return $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ])->getContent();
    }
}
