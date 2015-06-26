<?php
namespace Omeka\Form;

class SettingForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'administrator_email',
            'type' => 'Email',
            'options' => array(
                'label' => $translator->translate('Administrator Email'),
            ),
            'attributes' => array(
                'required' => true,
            ),
        ));

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

        $this->add(array(
            'name' => 'property_label_information',
            'type' => 'Select',
            'options' => array(
                'label' => $translator->translate('Property Label Information'),
                'info' => $translator->translate('The additional information that accompanies labels on resource pages.'),
                'value_options' => array (
                    'none' => 'None',
                    'vocab' => 'Show Vocabulary',
                    'term' => 'Show Term'
                ),
            )
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
