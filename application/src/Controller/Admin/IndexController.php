<?php
namespace Omeka\Controller\Admin;

use Omeka\Permissions\Acl;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    protected $acl;

    public function __construct(Acl $acl)
    {
        $this->acl = $acl;
    }

    public function browseAction()
    {
        // When the current user is not an admin, filter out sites where the
        // logged in user has no role.
        $siteQuery = [];
        $isAdmin = $this->acl->isAdminRole($this->identity()->getRole());
        if (!$isAdmin) {
            $siteQuery['user_has_role'] = '1';
        }

        $sitesResponse = $this->api()->search('sites', $siteQuery);
        $itemsResponse = $this->api()->search('items', ['limit' => 0]);
        $itemSetsResponse = $this->api()->search('item_sets', ['limit' => 0]);
        $vocabulariesResponse = $this->api()->search('vocabularies', ['limit' => 0]);
        $resourceTemplatesResponse = $this->api()->search('resource_templates', ['limit' => 0]);

        $view = new ViewModel;
        $view->setVariable('sites', $sitesResponse->getContent());
        $view->setVariable('itemCount', $itemsResponse->getTotalResults());
        $view->setVariable('itemSetCount', $itemSetsResponse->getTotalResults());
        $view->setVariable('vocabularyCount', $vocabulariesResponse->getTotalResults());
        $view->setVariable('resourceTemplateCount', $resourceTemplatesResponse->getTotalResults());
        return $view;
    }

    public function linkedResourcesAction()
    {
        $resource = $this->api()->read('resources', $this->params('id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resource', $resource);
        return $view;
    }
}
