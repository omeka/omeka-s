<?php
namespace Omeka\Form;

class VocabularyImportForm extends AbstractForm
{
    public function getFormName()
    {
        return 'import-vocabs';
    }

    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'file',
            'type' => 'file',
            'options' => array(
                'label' => $translator->translate('Vocabulary File'),
            ),
            'attributes' => array(
                'required' => true,
            ),
        ));

        $this->add(array(
            'name' => 'o:prefix',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Prefix'),
            ),
            'attributes' => array(
                'required' => true,
            ),
        ));

        $this->add(array(
            'name' => 'o:namespace_uri',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Namespace URI'),
            ),
            'attributes' => array(
                'required' => true,
            ),
        ));

        $this->add(array(
            'name' => 'o:label',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Label'),
            ),
            'attributes' => array(
                'required' => true,
            ),
        ));

        $this->add(array(
            'name' => 'o:comment',
            'type' => 'textarea',
            'options' => array(
                'label' => $translator->translate('Comment'),
            ),
        ));
    }
}
