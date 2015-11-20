<?php
namespace Omeka\Form;

class InstallationForm extends AbstractForm
{
    public function buildForm()
    {
        // By removing CSRF protection we're removing the need to use session
        // data during installation. This is needed for databse session storage.
        $this->remove('csrf');

        $translator = $this->getTranslator();

        $this->add([
            'name' => 'user',
            'type' => 'fieldset',
            'options' => [
                'label' => 'Create the first user',
            ],
        ]);
        $this->add([
            'name' => 'settings',
            'type' => 'fieldset',
             'options' => [
                'label' => 'Settings',
            ],
       ]);
        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => $translator->translate('Submit'),
            ],
        ]);

        $this->get('user')->add([
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
        $this->get('user')->add([
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
        $this->get('user')->add([
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
        $this->get('user')->add([
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
        $this->get('user')->add([
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
        $this->get('settings')->add([
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

        $timeZones = \DateTimeZone::listIdentifiers();
        $timeZones = array_combine($timeZones, $timeZones);
        $this->get('settings')->add([
            'name' => 'time_zone',
            'type' => 'Select',
            'options' => [
                'label' => $translator->translate('Time Zone'),
                'value_options' => $timeZones,
            ],
            'attributes' => [
                'id' => 'time-zone',
                'required' => true,
                'value' => date_default_timezone_get(),
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->get('user')->add([
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
        $inputFilter->get('user')->add([
            'name' => 'password-confirm',
            'required' => true,
            'validators' => [
                [
                    'name' => 'Identical',
                    'options' => [
                        'token' => 'password',
                        'message' => $translator->translate('The passwords did not match'),
                    ],
                ],
            ],
        ]);
        $inputFilter->get('user')->add([
            'name' => 'email-confirm',
            'required' => true,
            'validators' => [
                [
                    'name' => 'Identical',
                    'options' => [
                        'token' => 'email',
                        'message' => $translator->translate('The emails did not match'),
                    ],
                ],
            ],
        ]);
    }
}
