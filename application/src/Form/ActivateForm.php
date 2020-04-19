<?php
namespace Omeka\Form;

use Laminas\Form\Form;

class ActivateForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'password-confirm',
            'type' => 'Omeka\Form\Element\PasswordConfirm',
        ]);
        $this->get('password-confirm')->setIsRequired(true);
    }
}
