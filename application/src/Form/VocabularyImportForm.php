<?php
namespace Omeka\Form;

class VocabularyImportForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'file',
            'type' => 'file',
            'options' => [
                'label' => $translator->translate('Vocabulary File'),
                'info' => $translator->translate('Accepts the following formats: RDF/XML, RDF/JSON, N-Triples, and Turtle. See the Vocabulary Import Documentation for details.'),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:prefix',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Prefix'),
                'info' => $translator->translate('A concise vocabulary identifier, used as a shorthand proxy for the namespace URI.'),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:namespace_uri',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Namespace URI'),
                'info' => $translator->translate('The unique namespace URI used by the vocabulary to identify local member classes and properties.'),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:label',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Label'),
                'info' => $translator->translate('A human-readable title of the vocabulary.'),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:comment',
            'type' => 'textarea',
            'options' => [
                'label' => $translator->translate('Comment'),
                'info' => $translator->translate('A human-readable description of the vocabulary.'),
            ],
        ]);
    }
}
