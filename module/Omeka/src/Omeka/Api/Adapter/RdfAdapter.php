<?php
namespace Omeka\Api\Adapter;

use EasyRdf_Graph;
use EasyRdf_Literal;
use EasyRdf_Resource;
use Omeka\Api\Request;
use Omeka\Api\Response;

/**
 * RDF adapter.
 */
class RdfAdapter extends AbstractAdapter
{
    /**
     * Class types to import.
     * 
     * @var $classTypes
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
     * @var $propertyTypes
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
     * Import an RDF vocabulary, including its classes and properties.
     * 
     * @param mixed $data
     * @return mixed
     */
    public function create($data = null)
    {
        $response = new Response;
        $manager = $this->getServiceLocator()->get('ApiManager');

        $entityManager = $this->getServiceLocator()->get('EntityManager');
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
        $graph = new EasyRdf_Graph;
        if (isset($data['file']) && is_file($data['file'])) {
            $graph->parseFile($data['file'], 'rdfxml', $vocabulary['namespace_uri']);
        } else {
            $response->setStatus(Response::ERROR_NOT_FOUND);
            $response->addError('file', 'The RDF file is invalid.');
            return $response;
        }

        // Iterate through all resources of the graph instead of selectively by 
        // rdf:type becuase a resource may have more than one type, causing
        // illegal attempts to duplicate classes and properties.
        foreach ($graph->resources() as $resource) {

            // The resource must not be a blank node.
            if ($resource->isBnode()) {
                continue;
            }
            // The resource must be a local member of the vocabulary.
            if (!$this->isMember($resource, $vocabulary['namespace_uri'])) {
                continue;
            }

            // Create the vocabulary's classes.
            if (in_array($resource->type(), $this->classTypes)) {
                $request = new Request(Request::CREATE, 'resource_classes');
                $request->setContent(array(
                    'vocabulary' => array('id' => $vocabulary['id']),
                    'local_name' => $resource->localName(),
                    'label' => $this->getLabel($resource, $resource->localName()),
                    'comment' => $this->getComment($resource, $data),
                ));
                $responseClass = $manager->execute($request);
                if ($responseClass->isError()) {
                    $response->setStatus($responseClass->getStatus());
                    $response->mergeErrors($responseClass->getErrorStore());
                }
            }

            // Create the vocabulary's properties.
            if (in_array($resource->type(), $this->propertyTypes)) {
                $request = new Request(Request::CREATE, 'properties');
                $request->setContent(array(
                    'vocabulary' => array('id' => $vocabulary['id']),
                    'local_name' => $resource->localName(),
                    'label' => $this->getLabel($resource, $resource->localName()),
                    'comment' => $this->getComment($resource, $data),
                ));
                $responseProperty = $manager->execute($request);
                if ($responseProperty->isError()) {
                    $response->setStatus($responseProperty->getStatus());
                    $response->mergeErrors($responseProperty->getErrorStore());
                }
            }
        }

        if ($response->isError()) {
            $entityManager->getConnection()->rollback();
            $response->setStatus(Response::ERROR_INTERNAL);
        } else {
            $entityManager->getConnection()->commit();
        }

        return $response;
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
        if (isset($data['comment_property'])) {
            $property = $data['comment_property'];
        } else {
            $property = 'rdfs:comment';
        }
        $comment = $resource->get($property);
        if ($comment instanceof EasyRdf_Literal) {
            return $comment->getValue();
        }
    }
}
