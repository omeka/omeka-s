<?php
namespace Omeka\Controller\Admin;

use Omeka\Mvc\Exception;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class PropertyController extends AbstractActionController
{
    public function resourcePickerAction()
    {
        $this->setBrowseDefaults('label', 'asc');
        $query = $this->params()->fromQuery();
        $resourcePartial = $query['resource_partial'] ?? null;
        $valueType = $query['value_type'] ?? 'id';
        unset($query['resource_partial'], $query['value_type']);
        $response = $this->api()->search('properties', $query);
        $this->paginator($response->getTotalResults());

        $vocabularies = $this->api()->search('vocabularies', ['sort_by' => 'label'])->getContent();
        $searchValue = $query['search'] ?? '';
        $vocabularyId = $query['vocabulary_id'] ?? '';

        $view = new ViewModel;
        $view->setVariable('resources', $response->getContent());
        $view->setVariable('vocabularies', $vocabularies);
        $view->setVariable('searchValue', $searchValue);
        $view->setVariable('vocabularyId', $vocabularyId);
        $view->setVariable('expanded', (bool) $vocabularyId);
        $view->setVariable('valueType', $valueType);
        $view->setVariable('resourcePartial', $resourcePartial);
        $view->setTerminal(true);
        return $view;
    }

    public function showDetailsAction()
    {
        if (!$this->params('id')) {
            throw new Exception\NotFoundException;
        }

        $response = $this->api()->read('properties', $this->params('id'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('property', $response->getContent());
        return $view;
    }
}
