<?php
namespace Omeka\Form;

class SettingForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'pagination_per_page',
            'type' => 'Text',
            'options' => array(
                'label' => $translator->translate('Results per page'),
                'info' => $translator->translate('The maximum number of results per page on browse pages.'),
            ),
            'attributes' => array(
                'required' => true,
            ),
        ));

        $inputFilter = $this->getInputFilter();
        $inputFilter->add(array(
            'name' => 'pagination_per_page',
            'required' => true,
            'filters' => array(
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                array('name' => 'Digits')
            ),
        ));
    }
}
