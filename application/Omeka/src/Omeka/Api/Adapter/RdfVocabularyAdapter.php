<?php
namespace Omeka\Api\Adapter;

use EasyRdf_Graph;
use EasyRdf_Literal;
use EasyRdf_Resource;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Api\Response;

/**
 * RDF vocabulary adapter.
 */
class RdfVocabularyAdapter extends AbstractAdapter
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
     * Extract members (classes and properties) of the RDF vocabulary.
     *
     * Available keys:
     *
     * - strategy: (required) The import strategy to use (e.g. file, url).
     * - vocabulary[namespace_uri]: (required) Vocabulary namespace URI, as
     *   supported by the vocabulary entity adapter.
     * - format: (optional) The format of the RDF file. If not given, the RDF
     *   parser will attempt to guess the format.
     * - file: (required for "file" strategy) The RDF file in the
     *   /data/vocabularies directory.
     * - url: (required for "url" strategy) The URL of the RDF file.
     * - comment_property: (optional) The RDF property containing the preferred
     *   property comment (defaults to rdfs:comment)
     *
     * @param Request $request
     * @return Response
     */
    public function search(Request $request)
    {
        $data = $request->getContent();
        $t = $this->getTranslator();
        if (!isset($data['strategy'])) {
            throw new Exception\BadRequestException(
                $t->translate('No import strategy was specified.')
            );
        }
        if (!isset($data['vocabulary']['namespace_uri'])) {
            throw new Exception\BadRequestException(
                $t->translate('No vocabulary namespace URI was specified.')
            );
        }
        if (!isset($data['format'])) {
            // EasyRDF should guess the format if none given.
            $data['format'] = 'guess';
        }
        if (!isset($data['comment_property'])) {
            $data['comment_property'] = 'rdfs:comment';
        }

        $response = new Response;

        // Load the RDF graph.
        try {
            $graph = $this->getGraph($data);
        } catch (Exception\BadRequestException $e) {
            $response->setStatus(Response::ERROR_VALIDATION);
            $response->addError('rdf', $e->getMessage());
            return $response;
        }

        $response->setContent($this->extractMembers($graph, $data));
        return $response;
    }

    /**
     * Import an RDF vocabulary, including its classes and properties.
     *
     * Available keys:
     * 
     * - vocabulary: (required) Vocabulary data, as supported by the vocabulary
     *   entity adapter.
     * - strategy: (required) The import strategy to use (e.g. file, url).
     * - format: (optional) The format of the RDF file. If not given, the RDF
     *   parser will attempt to guess the format.
     * - file: (required for "file" strategy) The RDF file in the
     *   /data/vocabularies directory.
     * - url: (required for "url" strategy) The URL of the RDF file.
     * - comment_property: (optional) The RDF property containing the preferred
     *   property comment (defaults to rdfs:comment)
     *
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        $data = $request->getContent();
        $t = $this->getTranslator();
        if (!isset($data['vocabulary'])) {
            throw new Exception\BadRequestException(
                $t->translate('No vocabulary was specified.')
            );
        }
        if (!isset($data['strategy'])) {
            throw new Exception\BadRequestException(
                $t->translate('No import strategy was specified.')
            );
        }
        if (!isset($data['format'])) {
            // EasyRDF should guess the format if none given.
            $data['format'] = 'guess';
        }
        if (!isset($data['comment_property'])) {
            $data['comment_property'] = 'rdfs:comment';
        }

        $response = new Response;
        $manager = $this->getServiceLocator()->get('Omeka\ApiManager');

        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $entityManager->getConnection()->beginTransaction();

        // Create the vocabulary.
        $request = new Request(Request::CREATE, 'vocabularies');
        $request->setContent($data['vocabulary']);
        $responseVocab = $manager->execute($request);
        // If there are errors, stop importing the vocabulary.
        if ($responseVocab->isError()) {
            $entityManager->getConnection()->rollback();
            $response->setStatus($responseVocab->getStatus());
            $response->mergeErrors($responseVocab->getErrorStore());
            return $response;
        }
        $vocabulary = $responseVocab->getContent();

        // Load the RDF graph.
        try {
            $graph = $this->getGraph($data);
        } catch (Exception\BadRequestException $e) {
            $entityManager->getConnection()->rollback();
            $response->setStatus(Response::ERROR_VALIDATION);
            $response->addError('rdf', $e->getMessage());
            return $response;
        }

        $members = $this->extractMembers($graph, $data);
        
        // Add vocabulary data and batch create the classes.
        array_walk($members['classes'], function (&$class) use ($vocabulary) {
            $class['vocabulary'] = array('id' => $vocabulary['id']);
        });
        $responseClass = $manager->batchCreate('resource_classes', $members['classes']);
        if ($responseClass->isError()) {
            $response->setStatus($responseClass->getStatus());
            $response->mergeErrors($responseClass->getErrorStore());
        }

        // Add vocabulary data and batch create the properties.
        array_walk($members['properties'], function (&$property) use ($vocabulary) {
            $property['vocabulary'] = array('id' => $vocabulary['id']);
        });
        $responseProperty = $manager->batchCreate('properties', $members['properties']);
        if ($responseProperty->isError()) {
            $response->setStatus($responseProperty->getStatus());
            $response->mergeErrors($responseProperty->getErrorStore());
        }

        if ($response->isError()) {
            $entityManager->getConnection()->rollback();
            $response->setStatus(Response::ERROR_INTERNAL);
            return $response;
        }

        $entityManager->getConnection()->commit();
        return $response;
    }

    /**
     * Extract members (classes and properties) of the specified namespace.
     *
     * @param EasyRdf_Graph $graph
     * @param array $data
     * @return array
     */
    protected function extractMembers(EasyRdf_Graph $graph, array $data)
    {
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
            if (!$this->isMember($resource, $data['vocabulary']['namespace_uri'])) {
                continue;
            }
            // Get the vocabulary's classes.
            if (in_array($resource->type(), $this->classTypes)) {
                $members['classes'][] = array(
                    'local_name' => $resource->localName(),
                    'label' => $this->getLabel($resource, $resource->localName()),
                    'comment' => $this->getComment($resource, $data),
                );
            }
            // Get the vocabulary's properties.
            if (in_array($resource->type(), $this->propertyTypes)) {
                $members['properties'][] = array(
                    'local_name' => $resource->localName(),
                    'label' => $this->getLabel($resource, $resource->localName()),
                    'comment' => $this->getComment($resource, $data),
                );
            }
        }
        return $members;
    }

    /**
     * Get the RDF graph using the specified import strategy.
     *
     * @param array $data
     * @param string $namespaceUri
     * @return EasyRdf_Graph
     */
    protected function getGraph(array $data)
    {
        $t = $this->getTranslator();
        switch ($data['strategy']) {

            // Import from a file in /data/vocabularies directory.
            case 'file':
                if (!isset($data['file'])) {
                    throw new Exception\BadRequestException(
                        $t->translate('No file specified for the file import strategy.')
                    );
                }
                $file = OMEKA_PATH
                    . DIRECTORY_SEPARATOR . 'data'
                    . DIRECTORY_SEPARATOR . 'vocabularies'
                    . DIRECTORY_SEPARATOR . $data['file'];
                // Make sure the provided file path matches the expected path.
                if ($file != realpath($file) || !is_file($file)) {
                    throw new Exception\BadRequestException(
                        $t->translate('Invalid path to file.')
                    );
                }
                $graph = new EasyRdf_Graph;
                $graph->parseFile($file, $data['format'], $data['vocabulary']['namespace_uri']);
                return $graph;

            // Import from a URL.
            case 'url':
                if (!isset($data['url'])) {
                    throw new Exception\BadRequestException(
                        $t->translate('No URL specified for the URL import strategy.')
                    );
                }
                $graph = new EasyRdf_Graph;
                $graph->load($data['url'], $data['format']);
                return $graph;

            default:
                throw new Exception\BadRequestException(
                    $t->translate('Unsupported import strategy.')
                );
        }
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
    protected function getComment(EasyRdf_Resource $resource, array $data)
    {
        $comment = $resource->get($data['comment_property']);
        if ($comment instanceof EasyRdf_Literal) {
            return $comment->getValue();
        }
    }
}
