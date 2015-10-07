<?php
namespace Omeka\Form;

class ResourceTemplateForm extends AbstractForm
{
    protected $options = ['include_role' => false];

    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'o:label',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Label'),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:label',
            'required' => true,
        ]);
    }
}
