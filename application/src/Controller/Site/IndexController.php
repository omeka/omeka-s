<?php
namespace Omeka\Controller\Site;

use Omeka\Mvc\Exception;
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
            $firstPage = $pages[0];
            return $this->redirect()->toRoute('site/page', array(
                'site-slug' => $site->slug(),
                'page-slug' => $firstPage->slug(),
            ));
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        return $view;
    }

    protected function getSite()
    {
        return $this->api()->read('sites', array(
            'slug' => $this->params('site-slug')
        ))->getContent();
    }
}
