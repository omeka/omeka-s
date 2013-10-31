<?php
namespace Omeka\Api\Adapter;

use EasyRdf_Graph;
use Omeka\Api\Request;
use Omeka\Api\Response;

/**
 * RDF adapter.
 */
class Rdf extends AbstractAdapter
{
    /**
     * Import an RDF vocabulary, including its classes and properties.
     * 
     * @param mixed $data
     * @return mixed
     */
    public function create($data = null)
    {
        // Load the RDF graph.
        $graph = new EasyRdf_Graph;
        $graph->parseFile($data['file']);

        $response = new Response;
        $manager = $this->getServiceLocator()->get('ApiManager');

        // Create the vocabulary.
        $request = new Request(Request::CREATE, 'vocabularies');
        $request->setContent($data['vocabulary']);
        $responseVocab = $manager->execute($request);
        // If there are errors, stop importing the vocabulary.
        if ($responseVocab->isError()) {
            $response->mergeErrors($responseVocab->getErrorStore());
            return $response;
        }
        $vocabulary = $responseVocab->getContent();

        // Create the vocabulary's classes.
        foreach ($graph->allOfType('rdfs:Class') as $resource) {
            $request = new Request(Request::CREATE, 'resource_classes');
            $request->setContent(array(
                'vocabulary' => array('id' => $vocabulary['id']),
                'local_name' => $resource->localName(),
                'label' => $resource->label()->getValue(),
                'comment' => $resource->get('rdfs:comment')->getValue(),
            ));
            $request->setIsSubRequest(true);
            $responseClass = $manager->execute($request);
            if ($responseClass->isError()) {
                $response->mergeErrors($responseClass->getErrorStore());
            }
        }

        // Create the vocabulary's properties.
        foreach ($graph->allOfType('rdf:Property') as $resource) {
            $request = new Request(Request::CREATE, 'properties');
            $request->setContent(array(
                'vocabulary' => array('id' => $vocabulary['id']),
                'local_name' => $resource->localName(),
                'label' => $resource->label()->getValue(),
                'comment' => $resource->get('rdfs:comment')->getValue(),
            ));
            $request->setIsSubRequest(true);
            $responseProperty = $manager->execute($request);
            if ($responseProperty->isError()) {
                $response->mergeErrors($responseProperty->getErrorStore());
            }
        }

        // If there are no errors, invoke flush to create all classes and
        // properties in a single transaction.
        if (!$response->isError()) {
            $this->getServiceLocator()->get('EntityManager')->flush();
        }

        return $response;
    }
}
