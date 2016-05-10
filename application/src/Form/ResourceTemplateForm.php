<?php
namespace Omeka\Form;

class ResourceTemplateForm extends AbstractForm
{
    protected $options = ['include_role' => false];

    public function buildForm()
    {
        $this->add([
            'name' => 'o:label',
            'type' => 'Text',
            'options' => [
                'label' => 'Label', // @translate
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
