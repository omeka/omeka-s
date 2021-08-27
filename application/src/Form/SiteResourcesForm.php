<?php
namespace Omeka\Form;

use Omeka\Form\Element as OmekaElement;
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
                    'add' => 'Add - keep existing items and assign items from a new search query', // @translate
                    'replace' => 'Replace - unassign all items and assign items from a new search query', // @translate
                    'remove' => 'Remove - unassign items from a new search query', // @translate
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
        ]);
        $this->add([
            'type' => OmekaElement\Query::class,
            'name' => 'item_pool',
            'options' => [
                'label' => 'Search query', // @translate
                'query_resource_type' => 'items',
                'query_partial_excludelist' => [
                    'common/advanced-search/site',
                    'common/advanced-search/sort',
                ],
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
