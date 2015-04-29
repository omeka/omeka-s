<?php 
namespace Omeka\Form;

use Omeka\Form\ResourceForm;
use Omeka\Form\Element\ResourceSelect;

class ItemForm extends ResourceForm
{
    public function buildForm()
    {
        parent::buildForm();

        $this->setAttribute('enctype', 'multipart/form-data');
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
                    return $itemSet->displayTitle();
                }
            );

        if (!$itemSetSelect->getValueOptions()) {
            $itemSetSelect->setAttribute('disabled', true);
            $itemSetSelect->setAttribute('data-placeholder',
                $translator->translate('No item sets exist'));
        }
        $this->add($itemSetSelect);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'o:item_set',
            'required' => false,
        ));
    }
}
