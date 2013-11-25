<?php
namespace Omeka\Installation\Task;

/**
 * Install DCMI vocabulary.
 *
 * The DCMI Metadata Terms vocabulary is a unique case in Omeka, given that all
 * the classes and properties therein are read-only.
 */
class InstallDcmiVocabularyTask extends AbstractTask
{
    protected $vocabulary = array(
        'namespace_uri' => 'http://purl.org/dc/terms/',
        'label' => 'DCMI Metadata Terms',
        'comment' => 'The Dublin Core metadata terms are a set of vocabulary terms which can be used to describe resources for the purposes of discovery.',
    );

    /**
     * Install DCMI vocabulary.
     *
     * Installs the properties first, then installs the classes so the
     * properties can be assigned to them during the create.post event.
     */
    public function perform()
    {
        $am = $this->getServiceLocator()->get('ApiManager');
        $em = $this->getServiceLocator()->get('EntityManager');

        $em->getConnection()->beginTransaction();
        // Create the DCMI vocabulary.
        $response = $am->create('vocabularies', $this->vocabulary);
        if ($response->isError()) {
            $this->addErrorStore($response->getErrorStore());
            return;
        }
        $vocabulary = $response->getContent();

        // Get the members of the DCMI vocabulary.
        $response = $am->search('rdf_vocabulary', array(
            'vocabulary' => array(
                'namespace_uri' => $vocabulary['namespace_uri'],
            ),
            'strategy' => 'file',
            'file' => 'dcterms.rdf',
            'format' => 'rdfxml',
        ));
        if ($response->isError()) {
            $this->addErrorStore($response->getErrorStore());
            return;
        }
        $members = $response->getContent();

        // Install DCMI properties.
        foreach ($members['properties'] as $property) {
            $property['vocabulary'] = array('id' => $vocabulary['id']);
            $response = $am->create('properties', $property);
            if ($response->isError()) {
                $this->addErrorStore($response->getErrorStore());
                return;
            }
        }

        // Install DCMI classes.
        foreach ($members['classes'] as $class) {
            $class['vocabulary'] = array('id' => $vocabulary['id']);
            $response = $am->create('resource_classes', $class);
            if ($response->isError()) {
                $this->addErrorStore($response->getErrorStore());
                return;
            }
        }

        $em->getConnection()->commit();

        $this->addInfo('Successfully installed "DCMI Metdata Terms"');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Install DCMI Metadata Terms RDF vocabulary';
    }
}
