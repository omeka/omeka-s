<?php
namespace Omeka\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class SearchController extends AbstractActionController
{
    public function indexAction()
    {
        if ($this->getRequest()->isPost()) {
            $browseQuery= array();

            // Build value queries
            $value = $this->params()->fromPost('value', array('type' => array()));
            foreach ($value['type'] as $index => $type) {
                if (!isset($value['query'][$index])) {
                    continue;
                }
                $query = $value['query'][$index];
                $browseQuery['value'][$type][] = $query;
            }

            // Build property queries
            $property = $this->params()->fromPost('property', array('type' => array()));
            foreach ($property['type'] as $index => $type) {
                if (!isset($property['query'][$index]) || !isset($property['id'][$index])) {
                    continue;
                }
                $query = $property['query'][$index];
                $id = $property['id'][$index];
                $browseQuery['property'][$id][$type][] = $query;
            }

            // Build has property queries
            $hasProperty = $this->params()->fromPost('has_property', array('type' => array()));
            foreach ($hasProperty['type'] as $index => $type) {
                if (!isset($hasProperty['id'][$index])) {
                    continue;
                }
                $id = $hasProperty['id'][$index];
                $browseQuery['has_property'][$id] = $type;
            }

            return $this->redirect()->toRoute('admin/default', array(
                'controller' => $this->params()->fromPost('type', 'item'),
                'action' => 'browse',
            ), array(
                'query' => $browseQuery,
            ));
        }

        $response = $this->api()->search('vocabularies');
        if ($response->isError()) {
            $this->apiError($response);
            return;
        }
        $selectArray = array();
        foreach ($response->getContent() as $vocabulary) {
            foreach ($vocabulary->properties() as $property) {
                $selectArray[$vocabulary->label()][$property->id()] = $property->label();
            }
        }

        $view = new ViewModel;
        $view->setVariable('selectArray', $selectArray);
        return $view;
    }
}
