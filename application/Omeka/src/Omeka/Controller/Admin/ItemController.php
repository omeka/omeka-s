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
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $filter = new ResponseFilter;
        $response = $api->search('items', array());
        $items = $response->getContent();
        foreach ($items as &$item) {
            // Get the first dc:title value for the item.
            $response = $api->search('values', array(
                'resource' => array('id' => $item['id']),
                'property' => array('id' => 1), // dc:title
                'type' => 'literal',
                'limit' => 1,
            ));
            $item['title'] = $filter->get($response, 'value', array(
                'default' => '[no title]',
                'one' => true,
            ));
        }
        return new ViewModel(array('items' => $items));
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
