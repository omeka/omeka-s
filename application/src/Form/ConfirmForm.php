<?php
namespace Omeka\Form;

use Zend\Form\Form;

/**
 * General form for confirming an irreversable action in a sidebar.
 */
class ConfirmForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => [
                'value' => 'Confirm', // @translate
            ],
        ]);
    }

    public function setButtonLabel($label)
    {
        $this->get('submit')->setAttribute('value', $label);
    }
}
