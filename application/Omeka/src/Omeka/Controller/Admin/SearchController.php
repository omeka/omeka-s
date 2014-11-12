<?php
namespace Omeka\Controller\Admin;

use Zend\Form\Element\Select;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class SearchController extends AbstractActionController
{
    public function indexAction()
    {
        $response = $this->api()->search('vocabularies');
        if ($response->isError()) {
            $this->apiError($response);
            return;
        }

        // Build the property select object.
        $valueOptions = array();
        foreach ($response->getContent() as $vocabulary) {
            $options = array();
            foreach ($vocabulary->properties() as $property) {
                $options[$property->id()] = $property->label();
            }
            if (!$options) {
                continue;
            }
            $valueOptions[] = array(
                'label' => $vocabulary->label(),
                'options' => $options,
            );
        }
        $propertySelect = new Select;
        $propertySelect->setValueOptions($valueOptions);
        $propertySelect->setEmptyOption('Select Property');

        $view = new ViewModel;
        $view->setVariable('propertySelect', $propertySelect);
        return $view;
    }
}
