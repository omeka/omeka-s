<?php
namespace Omeka\Controller\Site;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $response = $this->api()->read('sites', array(
            'slug' => $this->params('site-slug')
        ));
        $site = $response->getContent();

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
}
