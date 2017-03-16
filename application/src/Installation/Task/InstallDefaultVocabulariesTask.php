<?php
namespace Omeka\Installation\Task;

use Omeka\Api\Exception\ValidationException;
use Omeka\Installation\Installer;

/**
 * Install default RDF vocabularies.
 */
class InstallDefaultVocabulariesTask implements TaskInterface
{
    /**
     * Default RDF vocabularies.
     *
     * @var array
     */
    protected $vocabularies = [
        [
            'vocabulary' => [
                'o:namespace_uri' => 'http://purl.org/dc/terms/',
                'o:prefix' => 'dcterms',
                'o:label' => 'Dublin Core',
                'o:comment' => 'Basic resource metadata (DCMI Metadata Terms)',
            ],
            'strategy' => 'file',
            'file' => 'dcterms.rdf',
            'format' => 'rdfxml',
        ],
        [
            'vocabulary' => [
                'o:namespace_uri' => 'http://purl.org/dc/dcmitype/',
                'o:prefix' => 'dctype',
                'o:label' => 'Dublin Core Type',
                'o:comment' => 'Basic resource types (DCMI Type Vocabulary)',
            ],
            'strategy' => 'file',
            'file' => 'dctype.rdf',
            'format' => 'rdfxml',
        ],
        [
            'vocabulary' => [
                'o:namespace_uri' => 'http://purl.org/ontology/bibo/',
                'o:prefix' => 'bibo',
                'o:label' => 'Bibliographic Ontology',
                'o:comment' => 'Bibliographic metadata (BIBO)',
            ],
            'strategy' => 'file',
            'file' => 'bibo.owl',
            'format' => 'rdfxml',
        ],
        [
            'vocabulary' => [
                'o:namespace_uri' => 'http://xmlns.com/foaf/0.1/',
                'o:prefix' => 'foaf',
                'o:label' => 'Friend of a Friend',
                'o:comment' => 'Relationships between people and organizations (FOAF)',
            ],
            'strategy' => 'file',
            'file' => 'foaf.rdf',
            'format' => 'rdfxml',
        ],
    ];

    public function perform(Installer $installer)
    {
        $rdfImporter = $installer->getServiceLocator()->get('Omeka\RdfImporter');
        $entityManager = $installer->getServiceLocator()->get('Omeka\EntityManager');

        foreach ($this->vocabularies as $vocabulary) {
            try {
                $response = $rdfImporter->import(
                    $vocabulary['strategy'],
                    $vocabulary['vocabulary'],
                    [
                        'file' => OMEKA_PATH . "/application/data/vocabularies/{$vocabulary['file']}",
                        'format' => $vocabulary['format'],
                    ]
                );
            } catch (ValidationException $e) {
                $installer->addErrorStore($e->getErrorStore());
                return;
            }
            $entityManager->clear();
        }
    }

    public function getVocabularies()
    {
        return $this->vocabularies;
    }
}
