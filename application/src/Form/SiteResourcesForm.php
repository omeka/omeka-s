<?php
namespace Omeka\Form;

use Laminas\Form\Form;

class SiteResourcesForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'radio',
            'name' => 'item_assignment_action',
            'options' => [
                'label' => 'Manage current items', // @translate
                'value_options' => [
                    'no_action' => 'Do nothing', // @translate
                    'add' => 'Add - keep existing items and assign items from a new search', // @translate
                    'replace' => 'Replace - unassign all items and assign items from a new search', // @translate
                    'remove' => 'Remove - unassign items from a new search', // @translate
                    'remove_all' => 'Remove all - unassign all items', // @translate
                ],
            ],
            'attributes' => [
                'value' => 'no_action',
            ],
        ]);
        $this->add([
            'type' => 'checkbox',
            'name' => 'save_search',
            'options' => [
                'label' => 'Keep this search', // @translate
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
