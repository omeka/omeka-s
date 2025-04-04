<?php

namespace RDFExport\Controller\Site;

require 'vendor/autoload.php';

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Http\Client;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Stream;
use EasyRdf\Graph;
use EasyRdf\RdfNamespace;
use Omeka\Entity\Item;  //  Omeka S entities
use Omeka\Entity\Value;
use Collecting\Entity\CollectingItem;  //  Collecting module entities
use Collecting\Entity\CollectingInput;
use Collecting\Entity\CollectingForm;

class IndexController extends AbstractActionController
{
    private $graphdbEndpoint = "http://localhost:7200/repositories/arch-project-shacl/rdf-graphs/service";
    private $graphdbQueryEndpoint = "http://localhost:7200/repositories/arch-project-shacl";
    private $dataGraphUri = "http://www.arch-project.com/data";

    public function indexAction()
    {
        $site = $this->currentSite();
        return new ViewModel(['site' => $site]);
    }

    public function uploadAction()  //  Or rename this to "exportAction"
    {
        $site = $this->currentSite();
        $logger = new Logger();
        $writer = new Stream(OMEKA_PATH . '/logs/graphdb-export.log');  //  Separate log file
        $logger->addWriter($writer);

        try {
            $graph = new Graph();
            $this->addRdfNamespaces();  //  Register RDF namespaces

            //  1.  Retrieve Data from Omeka S and Collecting Module

            //  Example:  Get all Collecting Forms for this site
            $collectingForms = $this->getCollectingForms($site->id());
            foreach ($collectingForms as $collectingForm) {
                $this->processCollectingForm($graph, $collectingForm);
            }

            //  2.  Serialize to RDF
            $turtle = new \EasyRdf\Serialiser\Turtle();
            $rdf = $turtle->serialise($graph, 'turtle');

            //  3.  Send to GraphDB
            $result = $this->sendToGraphDB($rdf);
            $this->messenger()->addSuccess($result);  //  Inform the user

        } catch (\Exception $e) {
            $errorMessage = 'Error exporting data: ' . $e->getMessage();
            $this->messenger()->addError($errorMessage);
            $logger->err($errorMessage);
        }

        return $this->redirect()->toRoute('site/rdf-export');  //  Redirect to a success/info page
    }

    private function addRdfNamespaces()
    {
        //  Register namespaces (from your TTL files)
        RdfNamespace::set('ah', 'http://www.purl.com/ah/ms/ahMS#');
        RdfNamespace::set('ah-vocab', 'http://www.purl.com/ah/kos#');
        RdfNamespace::set('excav', 'https://purl.org/ah/ms/excavationMS#');
        RdfNamespace::set('crm', 'http://www.cidoc-crm.org/cidoc-crm#');
        RdfNamespace::set('geo', 'http://www.w3.org/2003/01/geo/wgs84_pos#');
        RdfNamespace::set('xsd', 'http://www.w3.org/2001/XMLSchema#');
        RdfNamespace::set('provenance', 'http://example.org/provenance#');
        RdfNamespace::set('foaf', 'http://xmlns.com/foaf/0.1/');
    }

    private function getCollectingForms(int $siteId): array
    {
        $api = $this->api();
        $response = $api->search('collecting_forms', ['site_id' => $siteId]);
        return $response->getContent();
    }

    private function processCollectingForm(Graph $graph, CollectingForm $collectingForm)
    {
        $logger = new Logger();
        $writer = new Stream(OMEKA_PATH . '/logs/graphdb-export.log');  //  Separate log file
        $logger->addWriter($writer);

        //  Get all Collecting Items for this form
        $collectingItems = $this->getCollectingItems($collectingForm->getId());
        foreach ($collectingItems as $collectingItem) {
            try {
                $this->processCollectingItem($graph, $collectingItem);
            } catch (\Exception $e) {
                $logger->err("Error processing Collecting Item {$collectingItem->id()}: " . $e->getMessage());
            }
        }
    }

    private function getCollectingItems(int $formId): array
    {
        $api = $this->api();
        $response = $api->search('collecting_items', ['form_id' => $formId]);
        return $response->getContent();
    }

    private function processCollectingItem(Graph $graph, CollectingItem $collectingItem)
    {
        $item = $collectingItem->getItem(); // Assuming getItem() is the correct method to retrieve the associated Item
        $this->addArrowheadOrExcavationTriples($graph, $item, $collectingItem);
        $this->addProvenanceTriples($graph, $collectingItem, $item);
    }

    private function addArrowheadOrExcavationTriples(Graph $graph, Item $item, CollectingItem $collectingItem)
    {
        $logger = new Logger();
        $writer = new Stream(OMEKA_PATH . '/logs/graphdb-export.log');  //  Separate log file
        $logger->addWriter($writer);

        $itemUri = null;
        if ($item->getResourceClass() && $item->getResourceClass()->getUri() === 'http://www.purl.com/ah/ms/ahMS#Morphology') {
            $itemUri = 'http://www.purl.com/ah/ms/ahMS#arrowhead/' . $item->getId();
            $graph->addResource($itemUri, 'crm:E24_Physical_Man-Made_Thing', null);
        } elseif ($item->getResourceClass() && $item->getResourceClass()->getUri() === 'https://purl.org/ah/ms/excavationMS#Archaeologist') {
            $itemUri = 'https://purl.org/ah/ms/excavationMS#excavation/' . $item->getId();
            $graph->addResource($itemUri, 'crmarchaeo:A9_Archaeological_Excavation', null);
        } else {
            $logger->err("Item {$item->getId()} does not have a recognized Resource Class.");
            return; // Skip if resource class is not recognized
        }

        foreach ($item->getValues() as $value) {
            try {
                $this->addValueTriple($graph, $itemUri, $value);
            } catch (\Exception $e) {
                $logger->err("Error processing value for Item {$item->getId()}: " . $e->getMessage());
            }
        }
    }

    private function addValueTriple(Graph $graph, string $itemUri, Value $value)
    {
        $propertyName = $value->getProperty()->getLabel();
        $propertyUri = $value->getProperty()->getVocabulary()->getNamespaceUri() . $value->getProperty()->getLocalName();
        $valueType = $value->getType();
        $valueData = $value->getValue(); // Use getValue() to retrieve the value data

        switch ($propertyName) {
            case 'shape':
                $graph->addLiteral($itemUri, 'ah:shape', $valueData, 'ah-vocab:ah-shape');
                break;
            case 'variant':
                $graph->addLiteral($itemUri, 'ah:variant', $valueData, 'ah-vocab:ah-variant');
                break;
            case 'point':
                $graph->addLiteral($itemUri, 'ah:point', $valueData, 'xsd:boolean');
                break;
                //  Add cases for other properties from ahMS.ttl and excavationMS.ttl
            default:
                // Log if property is not handled
                $logger = new Logger();
                $writer = new Stream(OMEKA_PATH . '/logs/graphdb-export.log');
                $logger->addWriter($writer);
                $logger->info("Unhandled property: $propertyName ($propertyUri) with value: " . json_encode($valueData));
                break;
        }
    }

    private function addProvenanceTriples(Graph $graph, CollectingItem $collectingItem, Item $item)
    {
        $uploadEventUri = 'http://example.org/provenance/event/' . uniqid();
        $user = $collectingItem->getOwner();  // Assuming getOwner() retrieves the user
        $userUri = 'http://example.org/user/' . $user->getId();  // Get user ID
        $graph->addResource($uploadEventUri, 'provenance:UploadEvent', null);
        $itemUri = $this->dataGraphUri . '/item/' . $item->getId();  // Construct the item URI using its ID
        $graph->addResource($uploadEventUri, 'provenance:uploadedItem', $itemUri);  // Link to the constructed item URI
        $graph->addResource($uploadEventUri, 'provenance:uploader', $userUri);
        $graph->addLiteral($uploadEventUri, 'provenance:uploadTime', date(DATE_ATOM), 'xsd:dateTime');

        $graph->addResource($userUri, 'foaf:Agent', null);
        //$graph->addLiteral($userUri, 'foaf:accountName', $collectingItem->getOwner()->getEmail(), 'xsd:string');
    }

    private function sendToGraphDB(string $rdf)
    {
        $logger = new Logger();
        $writer = new Stream(OMEKA_PATH . '/logs/graphdb-export.log');  //  Separate log file
        $logger->addWriter($writer);

        try {
            $graphUri = $this->dataGraphUri;

            $validationResult = $this->validateData($rdf, $graphUri);
            if (!empty($validationResult)) {
                $errorMessage = 'Data upload failed: SHACL validation errors: ' . implode('; ', $validationResult);
                $logger->err($errorMessage);
                return $errorMessage;
            }

            $client = new Client();
            $fullUrl = $this->graphdbEndpoint . '?graph=' . urlencode($graphUri);
            $client->setUri($fullUrl);
            $client->setMethod('POST');
            $client->setHeaders(['Content-Type' => 'text/turtle']);
            $client->setRawBody($rdf);
            $client->setOptions(['timeout' => 60]);

            $response = $client->send();
            if ($response->isSuccess()) {
                return 'Data uploaded and validated successfully.';
            } else {
                $errorMessage = 'Failed to upload data: ' . $response->getStatusCode() . ' - ' . $response->getBody();
                $logger->err($errorMessage);
                return $errorMessage;
            }
        } catch (\Exception $e) {
            $errorMessage = 'Failed to upload data due to an exception: ' . $e->getMessage();
            $logger->err($errorMessage);
            return $errorMessage;
        }
    }

    private function validateData($data, $graphUri)
    {
        $errors = [];
        $logger = new Logger(); // Initialize logger here
        $writer = new Stream(OMEKA_PATH . '/logs/graphdb-errors.log');
        $logger->addWriter($writer);

        try {
            // 1. Prepare the validation query
            $query = "PREFIX sh: <http://www.w3.org/ns/shacl#>
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            
            SELECT ?message
            WHERE {
              GRAPH <http://www.arch-project.com/shapes> {
                ?shape a sh:NodeShape .
              }
              GRAPH <http://www.arch-project.com/data> {
                ?focusNode ?predicate ?object .
              }
              FILTER EXISTS {
                  GRAPH <http://www.arch-project.com/shapes> {
                    ?shape sh:targetClass ?targetClass .
                    FILTER NOT EXISTS { ?focusNode a ?targetClass }
                  }
              }
              FILTER EXISTS {
                  GRAPH <http://www.arch-project.com/shapes> {
                    ?shape sh:property ?propertyShape .
                    ?propertyShape sh:path ?path .
                    FILTER NOT EXISTS { ?focusNode ?path ?object }
                  }
              }
              BIND(CONCAT('Violation at node: ', str(?focusNode), ', predicate: ', str(?predicate), ', object: ', str(?object)) AS ?message)
            }
            ";

            // 2. Execute the validation query
            $client = new Client();
            $client->setUri($this->graphdbQueryEndpoint);
            $client->setMethod('POST');
            $client->setHeaders([
                'Content-Type' => 'application/sparql-query',
                'Accept' => 'application/sparql-results+json' // Crucial: Request JSON results
            ]);
            $client->setRawBody($query);
            $response = $client->send();

            if (!$response->isSuccess()) {
                $errorMessage = "SHACL validation query failed: " . $response->getStatusCode() . " - " . $response->getBody();
                $logger->err($errorMessage);
                error_log($errorMessage);
                return [$errorMessage];
            }

            $rawBody = $response->getBody();
            error_log("Raw GraphDB Response: " . $rawBody); // Keep logging the raw response

            $results = json_decode($rawBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $errorMessage = "Error decoding JSON response: " . json_last_error_msg() . " Raw Body: " . $rawBody; // Include raw body in error
                $logger->err($errorMessage);
                error_log($errorMessage);
                return [$errorMessage];
            }

            if (isset($results['results']['bindings'])) {
                foreach ($results['results']['bindings'] as $binding) {
                    $errors[] = $binding['message']['value'];
                }
            }
        } catch (\Exception $e) {
            $errorMessage = 'SHACL validation failed due to an exception: ' . $e->getMessage();
            $logger->err($errorMessage);
            error_log($errorMessage);
            return [$errorMessage];
        }

        return $errors;
    }
}