<?php
namespace Omeka\Form;

class ActivateForm extends AbstractForm
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
            'atttributes' => array(
                'id' => 'name',
                'required' => true,
            ),
        ));


        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'username',
            'required' => true,
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
    }
}