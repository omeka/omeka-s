<?php
namespace Omeka\Form;

use Zend\Form\Form;

class SiteResourcesForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'o:has_all_items',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Include all items', // @translate
            ],
            'attributes' => [
                'id' => 'summary',
                'value' => true,
            ],
        ]);
        $this->add([
            'type' => 'select',
            'name' => 'item_assignment_action',
            'options' => [
                'label' => 'Assignment action',
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
                'label' => 'Save this search',
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'item_assignment_action',
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'save_search',
            'allow_empty' => true,
        ]);
    }
}
