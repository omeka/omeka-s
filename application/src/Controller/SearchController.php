<?php
namespace Omeka\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class SearchController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        return $view;
    }

    public function resultsAction()
    {
        $view = new ViewModel;
        return $view;
    }

    public function itemsAction()
    {
        $view = new ViewModel;
        return $view;
    }

    public function itemsResultsAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('items', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());
        $items = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('items', $items);
        return $view;
    }
}
