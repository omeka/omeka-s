<?php
namespace Omeka\Form;

use Zend\Form\Form;

class ForgotPasswordForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'email',
            'type' => 'Email',
            'options' => [
                'label' => 'Email', // @translate
            ],
            'attributes' => [
                'id' => 'email',
                'required' => true,
            ],
        ]);
    }
}
