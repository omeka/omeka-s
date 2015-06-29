<?php
namespace Omeka\Form;

class ForgotPasswordForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'email',
            'type' => 'Email',
            'options' => array(
                'label' => $translator->translate('Email'),
            ),
            'attributes' => array(
                'id' => 'email',
                'required' => true,
            ),
        ));
    }
}

