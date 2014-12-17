<?php
namespace Omeka\Form;

/**
 * General form for confirming an irreversable action in a sidebar.
 */
class ConfirmForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $value = $this->getOption('button_value');
        if (!$value) {
            $value = $translator->translate('Confirm');
        }

        $this->add(array(
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => array(
                'value' => $value,
            ),
        ));
    }
}
