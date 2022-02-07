<?php
namespace Omeka\Form;

use Laminas\Form\Form;

/**
 * General form for confirming an irreversible action in a sidebar.
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
