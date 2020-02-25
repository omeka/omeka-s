<?php
namespace Omeka\Form;

use Zend\Form\Form;

class SiteResourcesForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'select',
            'name' => 'item_assignment_action',
            'options' => [
                'label' => 'Item assignment action',
                'empty_option' => '[No action]', // @translate
                'value_options' => [
                    'add' => 'Add items from the following search', // @translate
                    'replace' => 'Replace items with the following search', // @translate
                    'remove_all' => 'Remove all items', // @translate
                ],
            ],
        ]);

        $this->add([
            'type' => 'checkbox',
            'name' => 'save_search',
            'options' => [
                'label' => 'Save this search?',
            ],
            'attributes' => [
                'value' => true,
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'item_assignment_action',
            'allow_empty' => true,
        ]);
    }
}
