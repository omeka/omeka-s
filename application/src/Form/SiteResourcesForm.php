<?php
namespace Omeka\Form;

use Zend\Form\Form;

class SiteResourcesForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'o:assign_new_items',
            'type' => 'checkbox',
            'options' => [
                'label' => 'Assign new items', // @translate
                'info' => 'Select this if you want newly created items to be assigned to this site. Note that item owners may unassign their items at any time.', // @translate
            ],
            'attributes' => [
                'id' => 'assign_new_items',
                'value' => true,
            ],
        ]);
        $this->add([
            'type' => 'select',
            'name' => 'item_assignment_action',
            'options' => [
                'label' => 'Assignment action', // @translate
                'empty_option' => '[No action]', // @translate
                'value_options' => [
                    'add' => 'Add - keep existing items and assign items from the following search', // @translate
                    'replace' => 'Replace - unassign all items and assign items from the following search', // @translate
                    'remove' => 'Remove - unassign items from the following search', // @translate
                    'remove_all' => 'Remove all - unassign all items', // @translate
                ],
            ],
        ]);
        $this->add([
            'type' => 'checkbox',
            'name' => 'save_search',
            'options' => [
                'label' => 'Save this search', // @translate
                'info' => 'Use this as a convenient way to store a commonly used query. For example, you will likely want to save a search for periodic adding, but will not want to save a search for a one-time removal.', // @translate
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
