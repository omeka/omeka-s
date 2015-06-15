<?php
namespace Omeka\Form;

class InstallationForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'username',
            'type' => 'Text',
            'options' => array(
                'label' => $translator->translate('Username'),
            ),
            'attributes' => array(
                'id' => 'username',
                'required' => true,
            ),
        ));
        $this->add(array(
            'name' => 'password',
            'type' => 'Password',
            'options' => array(
                'label' => $translator->translate('Password'),
            ),
            'attributes' => array(
                'id' => 'password',
                'required' => true,
            ),
        ));
        $this->add(array(
            'name' => 'password-confirm',
            'type' => 'Password',
            'options' => array(
                'label' => $translator->translate('Confirm Password'),
            ),
            'attributes' => array(
                'id' => 'password-confirm',
                'required' => true,
            ),
        ));
        $this->add(array(
            'name' => 'name',
            'type' => 'Text',
            'options' => array(
                'label' => $translator->translate('Name'),
            ),
            'attributes' => array(
                'id' => 'name',
                'required' => true,
            ),
        ));
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
        $this->add(array(
            'name' => 'email-confirm',
            'type' => 'Email',
            'options' => array(
                'label' => $translator->translate('Confirm Email'),
            ),
            'attributes' => array(
                'id' => 'email-confirm',
                'required' => true,
            ),
        ));
        $this->add(array(
            'name' => 'administrator-email',
            'type' => 'Email',
            'options' => array(
                'label' => $translator->translate('Administrator Email'),
            ),
            'attributes' => array(
                'id' => 'administrator-email',
                'required' => true,
            ),
        ));
        $this->add(array(
            'name' => 'administrator-email-confirm',
            'type' => 'Email',
            'options' => array(
                'label' => $translator->translate('Confirm Administrator Email'),
            ),
            'attributes' => array(
                'id' => 'administrator-email-confirm',
                'required' => true,
            ),
        ));

        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'username',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'Regex',
                    'options' => array(
                        'pattern' => '/^\S+$/', // no whitespace
                    ),
                ),
            ),
        ));
        $inputFilter->add(array(
            'name' => 'password',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'StringLength',
                    'options' => array(
                        'min' => 6,
                    ),
                ),
            ),
        ));
        $inputFilter->add(array(
            'name' => 'password-confirm',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'Identical',
                    'options' => array(
                        'token' => 'password',
                    ),
                ),
            ),
        ));
        $inputFilter->add(array(
            'name' => 'email-confirm',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'Identical',
                    'options' => array(
                        'token' => 'email',
                    ),
                ),
            ),
        ));
        $inputFilter->add(array(
            'name' => 'administrator-email-confirm',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'Identical',
                    'options' => array(
                        'token' => 'administrator-email',
                    ),
                ),
            ),
        ));
    }
}
