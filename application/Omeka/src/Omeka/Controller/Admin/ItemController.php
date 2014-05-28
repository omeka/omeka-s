<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\ResponseFilter;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ItemController extends AbstractActionController
{
    public function indexAction()
    {}

    public function browseAction()
    {
        $view = new ViewModel;
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('items');
        if ($response->isError()) {
            print_r($response->getErrors());
            exit;
        }
        $view->setVariable('items', $response->getContent());
        return $view;
    }

    public function addAction()
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');

        // Handle the add item form.
        if ($this->getRequest()->isPost()) {

            // Create the item.
            $response = $api->create('items', array());
            if ($response->isError()) {
                print_r($response->getErrors());
                exit;
            }
            $item = $response->getContent();

            // Batch create the property values.
            $properties = $this->params()->fromPost('properties', array());
            $data = array();
            foreach ($properties as $propertyId => $values) {
                foreach ($values as $value) {
                    $data[] = array(
                        'resource' => array('id' => $item['id']),
                        'property' => array('id' => $propertyId),
                        'type'     => isset($value['type']) ? $value['type'] : null,
                        'value'    => isset($value['value']) ? $value['value'] : null,
                        'lang'     => isset($value['lang']) ? $value['lang'] : null,
                        'is_html'  => isset($value['is_html']) ? $value['is_html'] : null,
                    );
                }
            }
            $response = $api->batchCreate('values', $data);
            if ($response->isError()) {
                print_r($response->getErrors());
                exit;
            }
        }

        $response = $api->search('properties', array());
        return new ViewModel(array(
            'properties' => $response->getContent(),
        ));
    }

    public function editAction()
    {}
}
