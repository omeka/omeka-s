<?php
namespace Omeka\Form;

class ResourceTemplateForm extends AbstractForm
{
    protected $options = array('include_role' => false);

    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'o:label',
            'type' => 'Text',
            'options' => array(
                'label' => $translator->translate('Label'),
            ),
            'attributes' => array(
                'required' => true,
            ),
        ));
    }
}
