<?php
namespace Omeka\Form;

class UserKeyForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'new-key-label',
            'type' => 'Text',
            'options' => array(
                'label' => $translator->translate('New Key Label'),
            ),
            'attributes' => array(
                'id' => 'new-key-label',
            ),
        ));

        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'new-key-label',
            'required' => false,
            'validators' => array(
                array(
                    'name' => 'StringLength',
                    'options' => array(
                        'max' => 255,
                    ),
                ),
            ),
        ));
    }
}
