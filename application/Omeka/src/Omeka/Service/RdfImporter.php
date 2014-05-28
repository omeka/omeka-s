<?php
namespace Omeka\Service;

use EasyRdf_Graph;
use EasyRdf_Literal;
use EasyRdf_Resource;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class RdfImporter implements ServiceLocatorAwareInterface
{
    /**
     * Class types to import.
     * 
     * @var array
     */
    protected $classTypes = array(
        'rdfs:Class',
        'owl:Class',
    );

    /**
     * The property types to import.
     *
     * Not included are the OWL DL properties owl:AnnotationProperty and
     * owl:OntologyProperty because they typically serve internal annotative
     * purposes.
     * 
     * @var array
     */
    protected $propertyTypes = array(
        'rdf:Property',
        'owl:ObjectProperty',
        'owl:DatatypeProperty',
        'owl:SymmetricProperty',
        'owl:TransitiveProperty',
        'owl:FunctionalProperty',
        'owl:InverseFunctionalProperty',
    );

    /**
     * Get the members of the specified vocabulary.
     *
     * @param string $strategy The import strategy to use (e.g. "file", "url")
     * @param string $namespaceUri The namespace URI of the vocabulary
     * @param array $options
     * - format: (optional)  The format of the RDF file. If not given, the RDF
     *   parser will attempt to guess the format.
     * - file: (required for "file" strategy) The RDF file in the
     *   /data/vocabularies directory.
     * - url: (required for "url" strategy) The URL of the RDF file.
     * - comment_property: (optional) The RDF property containing the preferred
     *   property comment (defaults to "rdfs:comment")
     * @return array
     */
    public function getMembers($strategy, $namespaceUri, array $options = array())
    {
        if (!isset($options['format'])) {
            // EasyRDF should guess the format if none given.
            $options['format'] = 'guess';
        }
        if (!isset($options['comment_property'])) {
            $options['comment_property'] = 'rdfs:comment';
        }
        $graph = $this->getGraph($strategy, $namespaceUri, $options);
        return $this->extractMembers($graph, $namespaceUri, $options);
    }

    /**
     * Import an RDF vocabulary, including its classes and properties.
     *
     * @param string $strategy The import strategy to use (e.g. "file", "url")
     * @param string $vocabulary The vocabulary as supported by the vocabulary
     * entity adapter.
     * @param array $options See self::getMembers()
     */
    public function import($strategy, array $vocabulary, array $options = array())
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $entityManager->getConnection()->beginTransaction();

        $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');

        // Create the vocabulary.
        $responseVocabulary = $apiManager->create('vocabularies', $vocabulary);
        $vocabulary = $responseVocabulary->getContent()->jsonSerialize();

        // Get the RDF members.
        $members = $this->getMembers(
            $strategy, $vocabulary['namespace_uri'], $options
        );

        // Add vocabulary data and batch create the classes.
        array_walk($members['classes'], function (&$class) use ($vocabulary) {
            $class['vocabulary'] = array('id' => $vocabulary['id']);
        });
        $responseClass = $apiManager->batchCreate('resource_classes', $members['classes']);

        // Add vocabulary data and batch create the properties.
        array_walk($members['properties'], function (&$property) use ($vocabulary) {
            $property['vocabulary'] = array('id' => $vocabulary['id']);
        });
        $responseProperty = $apiManager->batchCreate('properties', $members['properties']);

        $entityManager->getConnection()->commit();
    }

    protected function getGraph($strategy, $namespaceUri, $options)
    {
        switch ($strategy) {

            // Import from a file in /data/vocabularies directory.
            case 'file':
                if (!isset($options['file'])) {
                    throw new \Exception('No file specified for the file import strategy.');
                }
                $file = OMEKA_PATH . "/data/vocabularies/{$options['file']}";
                // Make sure the provided file path matches the expected path.
                if ($file != realpath($file) || !is_file($file)) {
                    throw new \Exception('Invalid path to file.');
                }
                $graph = new EasyRdf_Graph;
                $graph->parseFile($file, $options['format'], $namespaceUri);
                return $graph;

            // Import from a URL.
            case 'url':
                if (!isset($options['url'])) {
                    throw new \Exception('No URL specified for the URL import strategy.');
                }
                $graph = new EasyRdf_Graph;
                $graph->load($options['url'], $options['format']);
                return $graph;

            default:
                throw new \Exception('Unsupported import strategy.');
        }
    }

    /**
     * Extract members (classes and properties) of the specified namespace.
     *
     * @param EasyRdf_Graph $graph
     * @param array $data
     * @return array
     */
    protected function extractMembers(
        EasyRdf_Graph $graph,
        $namespaceUri,
        array $options
    ) {
        $members = array(
            'classes' => array(),
            'properties' => array(),
        );
        // Iterate through all resources of the graph instead of selectively by 
        // rdf:type becuase a resource may have more than one type, causing
        // illegal attempts to duplicate classes and properties.
        foreach ($graph->resources() as $resource) {
            // The resource must not be a blank node.
            if ($resource->isBnode()) {
                continue;
            }
            // The resource must be a local member of the vocabulary.
            if (!$this->isMember($resource, $namespaceUri)) {
                continue;
            }
            // Get the vocabulary's classes.
            if (in_array($resource->type(), $this->classTypes)) {
                $members['classes'][] = array(
                    'local_name' => $resource->localName(),
                    'label' => $this->getLabel($resource, $resource->localName()),
                    'comment' => $this->getComment($resource, $options['comment_property']),
                );
            }
            // Get the vocabulary's properties.
            if (in_array($resource->type(), $this->propertyTypes)) {
                $members['properties'][] = array(
                    'local_name' => $resource->localName(),
                    'label' => $this->getLabel($resource, $resource->localName()),
                    'comment' => $this->getComment($resource, $options['comment_property']),
                );
            }
        }
        return $members;
    }

    /**
     * Determine whether a resource is a local member of the vocabulary.
     *
     * @param EasyRdf_Resource $resource
     * @param string $namespaceUri
     */
    protected function isMember(EasyRdf_Resource $resource, $namespaceUri)
    {
        $output = strncmp($resource->getUri(), $namespaceUri, strlen($namespaceUri));
        return $output === 0;
    }

    /**
     * Get the label from an RDF resource.
     *
     * @param EasyRdf_Resource $resource
     * @param string $default
     * @return string
     */
    protected function getLabel(EasyRdf_Resource $resource, $default)
    {
        $label = $resource->label();
        if ($label instanceof EasyRdf_Literal) {
            return $label->getValue();
        }
        return $default;
    }

    /**
     * Get the comment from an RDF resource.
     *
     * @param EasyRdf_Resource $resource
     * @param array $data
     * @return string
     */
    protected function getComment(EasyRdf_Resource $resource, $commentProperty)
    {
        $comment = $resource->get($commentProperty);
        if ($comment instanceof EasyRdf_Literal) {
            return $comment->getValue();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}
