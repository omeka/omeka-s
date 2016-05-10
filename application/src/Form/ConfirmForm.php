<?php
namespace Omeka\Form;

/**
 * General form for confirming an irreversable action in a sidebar.
 */
class ConfirmForm extends AbstractForm
{
    public function buildForm()
    {
        $value = $this->getOption('button_value');
        if (!$value) {
            $value = 'Confirm'; // @translate
        }

        $this->add([
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => [
                'value' => $value,
            ],
        ]);
    }
}
