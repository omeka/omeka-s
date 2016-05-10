<?php
namespace Omeka\Form;

class ModuleUninstallForm extends AbstractForm
{
    public function buildForm()
    {
        $this->add([
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => [
                'value' => 'Confirm Uninstall', // @translate
            ],
        ]);
    }
}
