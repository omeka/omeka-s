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

        // Create the vocabulary.
        $request = new Request(Request::CREATE, 'vocabularies');
        $request->setContent($data['vocabulary']);
        $responseVocab = $manager->execute($request);
        // If there are errors, stop importing the vocabulary.
        if ($responseVocab->isError()) {
            $response->setStatus($responseVocab->getStatus());
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

        if ($response->isError()) {
            $response->setStatus(Response::ERROR_INTERNAL);
        } else {
            // Invoke flush to create all classes and properties in a single
            // transaction.
            $this->getServiceLocator()->get('EntityManager')->flush();
        }

        return $response;
    }
}
