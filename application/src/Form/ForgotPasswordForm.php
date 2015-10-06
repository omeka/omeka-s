<?php
namespace Omeka\Form;

class ForgotPasswordForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'email',
            'type' => 'Email',
            'options' => [
                'label' => $translator->translate('Email'),
            ],
            'attributes' => [
                'id' => 'email',
                'required' => true,
            ],
        ]);
    }
}

