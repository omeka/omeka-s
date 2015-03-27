<?php 
namespace Omeka\Form;

use Omeka\Form\ResourceForm;
use Omeka\Form\Element\ResourceSelect;

class ItemForm extends ResourceForm {
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $serviceLocator = $this->getServiceLocator();
        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        $itemSetSelect = new ResourceSelect($serviceLocator);
        $itemSetSelect->setName('itemSet')
            ->setAttribute('required', false)
            ->setLabel($translator->translate('Item Sets'))
            ->setOption('info', $translator->translate('Select Items Sets for this resource.'))
            ->setEmptyOption($translator->translate('Select Item Set...'))
            ->setResourceValueOptions(
                'item_sets',
                array('owner_id' => $auth->getIdentity()),
                function ($itemSet, $serviceLocator) {
                    return $itemSet->displayTitle('[no title]');
                }
            );
        $this->add($itemSetSelect);
    }
}