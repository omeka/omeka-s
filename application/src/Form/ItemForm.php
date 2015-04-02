<?php 
namespace Omeka\Form;

use Omeka\Form\ResourceForm;
use Omeka\Form\Element\ResourceSelect;

class ItemForm extends ResourceForm {
    public function buildForm()
    {
        parent::buildForm();

        $translator = $this->getTranslator();

        $serviceLocator = $this->getServiceLocator();
        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        $itemSetSelect = new ResourceSelect($serviceLocator);
        $itemSetSelect->setName('o:item_set')
            ->setAttribute('required', false)
            ->setAttribute('multiple', true)
            ->setAttribute('id', 'select-item-set')
            ->setAttribute('data-placeholder', $translator->translate('Select Item Sets...'))
            ->setLabel($translator->translate('Item Sets'))
            ->setOption('info', $translator->translate('Select Items Sets for this resource.'))
            ->setResourceValueOptions(
                'item_sets',
                array('owner_id' => $auth->getIdentity()),
                function ($itemSet, $serviceLocator) {
                    return $itemSet->displayTitle('[no title]');
                }
            );
        $this->add($itemSetSelect);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'o:item_set',
            'required' => false,
        ));
    }
}
