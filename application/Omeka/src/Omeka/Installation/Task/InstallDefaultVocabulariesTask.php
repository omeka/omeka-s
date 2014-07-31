<?php
namespace Omeka\Installation\Task;

/**
 * Install default RDF vocabularies.
 */
class InstallDefaultVocabulariesTask extends AbstractTask
{
    /**
     * Default RDF vocabularies.
     *
     * @var array
     */
    protected $vocabularies = array(
        array(
            'vocabulary' => array(
                'namespace_uri' => 'http://purl.org/dc/terms/',
                'prefix' => 'dcterms',
                'label' => 'DCMI Metadata Terms',
                'comment' => 'The Dublin Core metadata terms are a set of vocabulary terms which can be used to describe resources for the purposes of discovery.',
            ),
            'strategy' => 'file',
            'file' => 'dcterms.rdf',
            'format' => 'rdfxml',
        ),
        array(
            'vocabulary' => array(
                'namespace_uri' => 'http://purl.org/ontology/bibo/',
                'prefix' => 'bibo',
                'label' => 'Bibliographic Ontology (BIBO)',
                'comment' => 'The Bibliographic Ontology (BIBO) is an ontology for the semantic Web to describe bibliographic things.',
            ),
            'strategy' => 'file',
            'file' => 'bibo.rdf',
            'format' => 'rdfxml',
        ),
        array(
            'vocabulary' => array(
                'namespace_uri' => 'http://purl.org/dc/dcmitype/',
                'prefix' => 'dcmitype',
                'label' => 'DCMI Type Vocabulary',
                'comment' => 'The DCMI Type Vocabulary provides a general, cross-domain list of approved terms that may be used as values for the Type element to identify the genre of a resource.',
            ),
            'strategy' => 'file',
            'file' => 'dctype.rdf',
            'format' => 'rdfxml',
        ),
        array(
            'vocabulary' => array(
                'namespace_uri' => 'http://xmlns.com/foaf/0.1/',
                'prefix' => 'foaf',
                'label' => 'Friend of a Friend (FOAF) vocabulary',
                'comment' => 'FOAF (an acronym of Friend of a friend) is a machine-readable ontology describing persons, their activities and their relations to other people and objects.',
            ),
            'strategy' => 'file',
            'file' => 'foaf.rdf',
            'format' => 'rdfxml',
        ),
        array(
            'vocabulary' => array(
                'namespace_uri' => 'http://www.w3.org/2003/01/geo/wgs84_pos#',
                'prefix' => 'geo',
                'label' => 'Basic Geo Vocabulary',
                'comment' => 'This is a basic RDF vocabulary that provides the Semantic Web community with a namespace for representing lat(itude), long(itude) and other information about spatially-located things.',
            ),
            'strategy' => 'file',
            'file' => 'geo.rdf',
            'format' => 'rdfxml',
        ),
    );
    
    /**
     * Install default RDF vocabularies.
     */
    public function perform()
    {
        $rdfImporter = $this->getServiceLocator()->get('Omeka\RdfImporter');
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');

        foreach ($this->vocabularies as $vocabulary) {
            $response = $rdfImporter->import(
                $vocabulary['strategy'],
                $vocabulary['vocabulary'],
                array(
                    'file' => $vocabulary['file'],
                    'format' => $vocabulary['format'],
                )
            );
            if ($response->isError()) {
                $this->addErrorStore($response->getErrorStore());
                return;
            }
            $entityManager->clear();
            $this->addInfo(sprintf(
                $this->getTranslator()->translate('Successfully installed "%s"'),
                $vocabulary['vocabulary']['label']
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getTranslator()->translate('Install default RDF vocabularies');
    }
}
