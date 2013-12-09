<?php
namespace Omeka\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;

class ItemController extends AbstractActionController
{
    public function indexAction()
    {}

    public function browseAction()
    {}

    public function addAction()
    {
        // Handle the add item form.
        if ($this->getRequest()->isPost()) {
            $apiManager = $this->getServiceLocator()->get('ApiManager');

            // Create the item.
            $response = $apiManager->create('items', array());
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
            $response = $apiManager->batchCreate('values', $data);
            if ($response->isError()) {
                print_r($response->getErrors());
                exit;
            }
        }
    }

    public function editAction()
    {}
}
