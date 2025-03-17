<?php
namespace Collecting\Controller\SiteAdmin;

use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class ItemController extends AbstractActionController
{
    public function indexAction()
    {
        $site = $this->currentSite();
        $cForm = $this->collectingCurrentForm();
        $this->getRequest()->getQuery()->set('form_id', $cForm->id());

        $this->setBrowseDefaults('id');
        $response = $this->api()->search('collecting_items', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $cItems = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('cForm', $cForm);
        $view->setVariable('cItems', $cItems);
        return $view;
    }

    public function showDetailsAction()
    {
        $cItem = $this->api()
            ->read('collecting_items', $this->params('item-id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('cItem', $cItem);
        return $view;
    }

    public function updateStatusesAction()
    {
        $statuses = $this->params()->fromPost('statuses', []);
        foreach ($statuses as $cItemId => $status) {
            $cItem = $this->api()->read('collecting_items', $cItemId)->getContent();
            $item = $cItem->item();
            if ('needs_review' === $status) {
                if (!$cItem->reviewed() && !$item->isPublic()) {
                    continue;
                }
                $reviewed = false;
                $isPublic = false;
            } elseif ('public' === $status) {
                if ($cItem->reviewed() && $item->isPublic()) {
                    continue;
                }
                $reviewed = true;
                $isPublic = true;
            } elseif ('private' === $status) {
                if ($cItem->reviewed() && !$item->isPublic()) {
                    continue;
                }
                $reviewed = true;
                $isPublic = false;
            }
            // Update the status using partial updates.
            $this->api()->update('collecting_items', $cItem->id(), [
                'o-module-collecting:reviewed' => $reviewed,
            ], [], ['isPartial' => true]);
            $this->api()->update('items', $cItem->item()->id(), [
                'o:is_public' => $isPublic,
            ], [], ['isPartial' => true]);
        }
        $this->messenger()->addSuccess('Statuses successfully updated'); // @translate
        return $this->redirect()->toRoute(null, ['action' => 'index'], true);
    }
}
