<?php
namespace Omeka\Controller;

use Omeka\Api\Exception as ApiException;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        // Redirect to default site, if set
        $defaultSiteId = $this->settings()->get('default_site');
        if ($defaultSiteId) {
            try {
                $defaultSiteResponse = $this->api()->read('sites', $defaultSiteId);
                $defaultSite = $defaultSiteResponse->getContent();
                return $this->redirect()->toUrl($defaultSite->siteUrl());
            } catch (ApiException\NotFoundException $e) {
                // Consume error if default site isn't found
            }
        }

        $this->setBrowseDefaults('title', 'asc');
        $response = $this->api()->search('sites', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());

        $view = new ViewModel;
        $view->setVariable('sites', $response->getContent());
        return $view;
    }
}
