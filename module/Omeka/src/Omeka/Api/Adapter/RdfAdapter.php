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
     * @var $classTypes
     */
    protected $classTypes = array(
        'rdfs:Class',
        'owl:Class',
    );

    /**
     * @var $propertyTypes
     */
    protected $propertyTypes = array(
        'rdf:Property',
        'owl:ObjectProperty',
        'owl:AnnotationProperty',
        'owl:DatatypeProperty',
        'owl:SymmetricProperty',
        'owl:TransitiveProperty',
        'owl:FunctionalProperty',
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

        // Load the RDF graph.
        $graph = new EasyRdf_Graph;
        if (isset($data['file']) && is_file($data['file'])) {
            $graph->parseFile($data['file']);
        } else {
            $response->setStatus(Response::ERROR_NOT_FOUND);
            $response->addError('file', 'The RDF file is invalid.');
            return $response;
        }

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

        foreach ($graph->resources() as $resource) {

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
            $response->setStatus(Response::ERROR_INTERNAL);
            $entityManager->getConnection()->rollback();
        } else {
            $entityManager->getConnection()->commit();
        }

        return $response;
    }

    /**
     * Determine whether a resource is a member of a namespace URI.
     *
     * @todo Once the following EasyRDF issue is resolved, RDF graphs that use
     * xml:base in the root element (such as SKOS and BIBO) can be imported.
     * https://github.com/njh/easyrdf/issues/157
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
