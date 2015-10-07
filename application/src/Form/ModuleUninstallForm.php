<?php
namespace Omeka\Form;

class ModuleUninstallForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add([
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => [
                'value' => $translator->translate('Confirm Uninstall'),
            ],
        ]);
    }
}
