<?php
namespace Omeka\Controller;

use EasyRdf_Graph;
use EasyRdf_Serialiser;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class OmekaController extends AbstractActionController
{
    const CLASS_URI = 'http://www.w3.org/2000/01/rdf-schema#Class';
    const PROPERTY_URI = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Property';

    public function indexAction()
    {
        // Get the custom vocabulary for this instance.
        $content = $this->api()
            ->search('vocabularies', array('prefix' => 'omeka'))
            ->getContent();
        $omekaVocabulary = $content[0];

        // Build the RDF graph of the vocabulary.
        $graph = new EasyRdf_Graph;
        $namespaceUri = $omekaVocabulary->namespaceUri();
        foreach ($omekaVocabulary->properties() as $property) {
            $resource = $graph->resource($namespaceUri . $property->localName());
            $resource->set('rdf:type', $graph->resource(self::PROPERTY_URI));
            $resource->set('rdfs:label', $property->label());
            $resource->set('rdfs:comment', $property->comment());
        }
        foreach ($omekaVocabulary->resourceClasses() as $resourceClass) {
            $resource = $graph->resource($namespaceUri . $resourceClass->localName());
            $resource->set('rdf:type', $graph->resource(self::CLASS_URI));
            $resource->set('rdfs:label', $resourceClass->label());
            $resource->set('rdfs:comment', $resourceClass->comment());
        }

        $request = $this->getRequest();
        $response = $this->getResponse();

        // Set default format and content type.
        $format = 'rdfxml';
        $contentType = 'application/xml';

        // Set the format and content type according to Accept headers, if any.
        foreach ($request->getHeader('Accept')->getPrioritized() as $accept) {
            if ('text/turtle' == $accept->getTypeString()) {
                $format = 'turtle';
                $contentType = 'text/turtle';
                break;
            } elseif ('application/n-triples' == $accept->getTypeString()) {
                $format = 'ntriples';
                $contentType = 'application/n-triples';
                break;
            } elseif ('application/rdf+xml' == $accept->getTypeString()) {
                $contentType = 'application/rdf+xml';
                break;
            }
        }

        $response->setContent($graph->serialise($format));
        $response->getHeaders()->addHeaderLine('Content-Type', $contentType);
        return $response;
    }
}
