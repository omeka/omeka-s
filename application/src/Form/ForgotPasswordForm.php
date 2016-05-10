<?php
namespace Omeka\Form;

class ForgotPasswordForm extends AbstractForm
{
    public function buildForm()
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
