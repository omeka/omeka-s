<?php
namespace Omeka\Api\Adapter;

use EasyRdf_Graph;
use Omeka\Api\Request;
use Omeka\Api\Response;

class Rdf extends AbstractAdapter
{
    /**
     * @param mixed $data
     * @return mixed
     */
    public function create($data = null)
    {
        $response = new Response;

        // Require a vocabulary ID.
        if (!isset($data['vocabulary']['id'])) {
            $response->setStatus(Response::ERROR_VALIDATION);
            $response->addError('vocabulary', 'A vocabulary ID is required.');
            return $response;
        }

        // Require a valid vocabulary.
        $request = new Request(Request::READ, 'vocabularies');
        $request->setId($data['vocabulary']['id']);
        $vocabularyResponse = $manager->execute($request);
        if ($vocabularyResponse->isError()) {
            $response->setStatus(Response::ERROR_NOT_FOUND);
            $response->addError('vocabulary', sprintf(
                'A vocabulary with ID "%s" was not found',
                $data['vocabulary']['id']
            ));
            return $response;
        }

        // Load the RDF graph.
        $graph = new EasyRdf_Graph;
        $graph->parseFile($data['file']);

        // Set the response content.
        $content = array(
            'vocabulary' => $vocabularyResponse->getContent(),
            'resource_classes' => array(),
            'properties' => array(),
        );

        // Create the vocabulary's classes.
        $classes = $graph->allOfType('rdfs:Class');
        foreach ($classes as $resource) {
            $class = array(
                'vocabulary' => array('id' => $data['vocabulary']['id']),
                'local_name' => $resource->localName(),
                'label' => $resource->label()->getValue(),
                'comment' => $resource->get('rdfs:comment')->getValue(),
            );
            $request = new Request(Request::CREATE, 'resource_classes');
            $request->setContent($class);
            $classResponse = $manager->execute($request);
            $content['resource_classes'][] = $classResponse->getContent();
            if ($classResponse->isError()) {
                $response->mergeErrors($classResponse->getErrorStore());
            }
        }

        // Create the vocabulary's properties.
        $properties = $graph->allOfType('rdf:Property');
        foreach ($properties as $resource) {
            $property = array(
                'vocabulary' => array('id' => $data['vocabulary']['id']),
                'local_name' => $resource->localName(),
                'label' => $resource->label()->getValue(),
                'comment' => $resource->get('rdfs:comment')->getValue(),
            );
            $request = new Request(Request::CREATE, 'properties');
            $request->setContent($property);
            $propertyResponse = $manager->execute($request);
            $content['properties'][] = $propertyResponse->getContent();
            if ($propertyResponse->isError()) {
                $response->mergeErrors($propertyResponse->getErrorStore());
            }
        }

        $response->setContent($content);
        return $response;
    }
}
