<?php
namespace Omeka\Form;

class ModuleUninstallForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => array(
                'value' => $translator->translate('Confirm Uninstall'),
            ),
        ));
    }
}
