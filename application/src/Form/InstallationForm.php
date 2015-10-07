<?php
namespace Omeka\Form;

class InstallationForm extends AbstractForm
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
        $this->add([
            'name' => 'email-confirm',
            'type' => 'Email',
            'options' => [
                'label' => $translator->translate('Confirm Email'),
            ],
            'attributes' => [
                'id' => 'email-confirm',
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'password',
            'type' => 'Password',
            'options' => [
                'label' => $translator->translate('Password'),
            ],
            'attributes' => [
                'id' => 'password',
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'password-confirm',
            'type' => 'Password',
            'options' => [
                'label' => $translator->translate('Confirm Password'),
            ],
            'attributes' => [
                'id' => 'password-confirm',
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'name',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Display Name'),
            ],
            'attributes' => [
                'id' => 'name',
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'installation_title',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Installation Title'),
            ],
            'attributes' => [
                'id' => 'installation-title',
                'required' => true,
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'password',
            'required' => true,
            'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'min' => 6,
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'password-confirm',
            'required' => true,
            'validators' => [
                [
                    'name' => 'Identical',
                    'options' => [
                        'token' => 'password',
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'email-confirm',
            'required' => true,
            'validators' => [
                [
                    'name' => 'Identical',
                    'options' => [
                        'token' => 'email',
                    ],
                ],
            ],
        ]);
    }
}
