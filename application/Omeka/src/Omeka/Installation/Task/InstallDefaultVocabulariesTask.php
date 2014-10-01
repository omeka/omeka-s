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
                'o:namespace_uri' => 'http://purl.org/dc/terms/',
                'o:prefix' => 'dcterms',
                'o:label' => 'Dublin Core',
                'o:comment' => 'Basic resource metadata (DCMI Metadata Terms)',
            ),
            'strategy' => 'file',
            'file' => 'dcterms.rdf',
            'format' => 'rdfxml',
        ),
        array(
            'vocabulary' => array(
                'o:namespace_uri' => 'http://purl.org/ontology/bibo/',
                'o:prefix' => 'bibo',
                'o:label' => 'Bibliographic Ontology',
                'o:comment' => 'Bibliographic metadata (BIBO)',
            ),
            'strategy' => 'file',
            'file' => 'bibo.rdf',
            'format' => 'rdfxml',
        ),
        array(
            'vocabulary' => array(
                'o:namespace_uri' => 'http://purl.org/dc/dcmitype/',
                'o:prefix' => 'dcmitype',
                'o:label' => 'Dublin Core Type',
                'o:comment' => 'Basic resource types (DCMI Type Vocabulary)',
            ),
            'strategy' => 'file',
            'file' => 'dctype.rdf',
            'format' => 'rdfxml',
        ),
        array(
            'vocabulary' => array(
                'o:namespace_uri' => 'http://xmlns.com/foaf/0.1/',
                'o:prefix' => 'foaf',
                'o:label' => 'Friend of a Friend',
                'o:comment' => 'Relationships between people and organizations (FOAF)',
            ),
            'strategy' => 'file',
            'file' => 'foaf.rdf',
            'format' => 'rdfxml',
        ),
        array(
            'vocabulary' => array(
                'o:namespace_uri' => 'http://www.w3.org/2003/01/geo/wgs84_pos#',
                'o:prefix' => 'geo',
                'o:label' => 'Geo',
                'o:comment' => 'Basic spatial metadata (Basic Geo Vocabulary)',
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
                $vocabulary['vocabulary']['o:label']
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
