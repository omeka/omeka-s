<?php
namespace Omeka\Form;

class VocabularyImportForm extends AbstractForm
{
    public function buildForm()
    {
        $translator = $this->getTranslator();

        $this->add(array(
            'name' => 'file',
            'type' => 'file',
            'options' => array(
                'label' => $translator->translate('Vocabulary File'),
                'info' => $translator->translate('Accepts the following formats: RDF/XML, RDF/JSON, N-Triples, and Turtle. Imports the following vocabulary members: rdfs:Class, owl:Class, rdf:Property, owl:ObjectProperty, owl:DatatypeProperty, owl:SymmetricProperty, owl:TransitiveProperty, owl:FunctionalProperty, owl:InverseFunctionalProperty'),
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
                'info' => $translator->translate('A concise vocabulary identifier, used as a shorthand proxy for the namespace URI.'),
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
                'info' => $translator->translate('The unique namespace URI used by the vocabulary to identify local member classes and properties.'),
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
                'info' => $translator->translate('A human-readable title of the vocabulary.'),
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
                'info' => $translator->translate('A human-readable description of the vocabulary.'),
            ),
        ));
    }
}
