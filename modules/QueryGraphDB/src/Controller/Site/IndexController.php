<?php

namespace QueryGraphDB\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Http\Client;

class IndexController extends AbstractActionController
{
    protected $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function indexAction()
{
    $currentSubjectFilter = $this->params()->fromQuery('subject');
    $currentSecondaryFilters = $this->params()->fromQuery('filters', []);

    // Define GraphDB endpoint
    $graphdbEndpoint = "http://localhost:7200/repositories/arch-project-shacl";
    $sparqlQuery = ""; // Initialize the query

    switch ($currentSubjectFilter) {
        
 
        case 'arrowheads':
            $sparqlQuery = "
                PREFIX ah: <http://www.purl.com/ah/ms/ahMS#>
                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                PREFIX crm: <http://www.cidoc-crm.org/cidoc-crm/>
                PREFIX ah-shape: <http://www.purl.com/ah/kos/ah-shape/>
                PREFIX dcterms: <http://purl.org/dc/terms/>
                PREFIX crmsci: <http://cidoc-crm.org/extensions/crmsci/>
                PREFIX ah-variant: <http://www.purl.com/ah/kos/ah-variant/>
                PREFIX ah-base: <http://www.purl.com/ah/kos/ah-base/>
                PREFIX ah-chippingMode: <http://www.purl.com/ah/kos/ah-chippingMode/>
                PREFIX ah-chippingDirection: <http://www.purl.com/ah/kos/ah-chippingDirection/>
                PREFIX ah-chippingDelineation: <http://www.purl.com/ah/kos/ah-chippingDelineation/>
                PREFIX ah-chippingShape: <http://www.purl.com/ah/kos/ah-chippingShape/>
                PREFIX ah-chippingLocation: <http://www.purl.com/ah/kos/ah-chippingLocation/>
                PREFIX crmarchaeo: <http://www.cidoc-crm.org/extensions/crmarchaeo/>
                PREFIX foaf: <http://xmlns.com/foaf/0.1/>                
                PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
                PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
                                
            
                SELECT ?object
                WHERE {
                    ?subject a crm:E24_Physical_Man-Made_Thing ;    

                    BIND(rdf:type AS ?predicate) .
                    OPTIONAL { ?subject dcterms:identifier ?object . }
                }";

            if (!empty($currentSecondaryFilters)) {
                $filterConditions = [];
        
                if (isset($currentSecondaryFilters['shape']) && is_array($currentSecondaryFilters['shape']) && !empty($currentSecondaryFilters['shape'])) {
                    $shapes = array_map(function ($shape) {
                        return "ah:shape ah-shape:" . $shape . "."; // Construct the desired format
                    }, $currentSecondaryFilters['shape']);
                    $filterConditions = array_merge($filterConditions, $shapes); // Add to filter conditions
                    error_log(implode(", ", $filterConditions), 3, 'logs/filter.log'); // Log the filter conditions
                }

                if (isset($currentSecondaryFilters['variant']) && is_array($currentSecondaryFilters['variant']) && !empty($currentSecondaryFilters['variant'])) {
                    $variants = array_map(function ($variant) {
                        return "ah:variant ah-variant:" . $variant . "."; // Construct the desired format
                    }, $currentSecondaryFilters['variant']);
                    $filterConditions = array_merge($filterConditions, $variants); // Add to filter conditions
                    error_log(implode(", ", $filterConditions), 3, 'logs/filter.log'); // Log the filter conditions
                }
        
              
                if (isset($currentSecondaryFilters['base']) && is_array($currentSecondaryFilters['base']) && !empty($currentSecondaryFilters['base'])) {
                    $bases = array_map(function ($base) {
                        return "ah:hasMorphology ?morphology . ?morphology ah:base ah-base:" . $base . "."; // Construct the desired format
                    }, $currentSecondaryFilters['base']);
                    $filterConditions = array_merge($filterConditions, $bases); // Add to filter conditions
                    error_log(implode(", ", $filterConditions), 3, 'logs/filter.log'); // Log the filter conditions
                }

                // not working yet because the conversion is uploading ns1 insteadfh of chippingMode...
                if (isset($currentSecondaryFilters['mode']) && is_array($currentSecondaryFilters['mode']) && !empty($currentSecondaryFilters['mode'])) {
                    $modes = array_map(function ($mode) {
                        return "ah:hasTypometry ?typometry .
    ?typometry ah:hasChipping ?chipping .
    ?chipping ah:mode ?targetChippingMode .
    FILTER (?targetChippingMode = ah-chippingMode:" . $mode . "."; // Construct the desired format

                    }, $currentSecondaryFilters['mode']);
                    $filterConditions = array_merge($filterConditions, $modes); // Add to filter conditions
                    error_log(implode(", ", $filterConditions), 3, 'logs/filter.log'); // Log the filter conditions
                }
        
               
                // not working yet because the conversion is uploading ns1 insteadfh of chippingDirection...
                if (isset($currentSecondaryFilters['direction']) && is_array($currentSecondaryFilters['direction']) && !empty($currentSecondaryFilters['direction'])) {
                    $directions = array_map(function ($direction) {
                        return "<ah-chippingDirection:" . $direction . ">"; // Assuming your direction values are local names
                    }, $currentSecondaryFilters['direction']);
                    $filterConditions[] = " ?subject ah:direction IN (" . implode(", ", $directions) . ") ";
                }
        
                if (isset($currentSecondaryFilters['delineation']) && is_array($currentSecondaryFilters['delineation']) && !empty($currentSecondaryFilters['delineation'])) {
                    $delineations = array_map(function ($delineation) {
                        return "ah:hasTypometry ?typometry .
    ?typometry ah:hasChipping ?chipping .
    ?chipping ah:delineation ah-chippingDelineation:" . $delineation . "."; // Construct the desired format
                    }, $currentSecondaryFilters['delineation']);
                    $filterConditions = array_merge($filterConditions, $delineations); // Add to filter conditions
                    error_log(implode(", ", $filterConditions), 3, 'logs/filter.log'); // Log the filter conditions
                }
        
                if (isset($currentSecondaryFilters['chippingShape']) && is_array($currentSecondaryFilters['chippingShape']) && !empty($currentSecondaryFilters['chippingShape'])) {
                    $chippingShapes = array_map(function ($chippingShape) {
                        return "ah:hasTypometry ?typometry . ?typometry ah:hasChipping ?chipping . ?chipping ah:chippingShape ah-chippingShape:" . $chippingShape . "."; // Construct the desired format

                    }, $currentSecondaryFilters['chippingShape']);
                    $filterConditions = array_merge($filterConditions, $chippingShapes); // Add to filter conditions
                    error_log(implode(", ", $filterConditions), 3, 'logs/filter.log'); // Log the filter conditions
                }
        
                // Handle boolean filters (point, body, amplitude, orientation) NOT WORKING
                foreach (['point', 'body', 'amplitude', 'orientation'] as $prop) {
                    if (isset($currentSecondaryFilters[$prop]) && in_array($currentSecondaryFilters[$prop], ['true', 'false'])) {
                        $filterConditions[] = " ?subject ah:" . $prop . " = " . $currentSecondaryFilters[$prop] . " ";
                    }
                }
        
                // Handle multi-value chipping locations (Side and Transversal) not working
                foreach (['chippinglocation-Side', 'chippinglocation-Transversal'] as $prop) {
                    if (isset($currentSecondaryFilters[$prop]) && is_array($currentSecondaryFilters[$prop]) && !empty($currentSecondaryFilters[$prop])) {
                        $locations = array_map(function ($location) {
                            return "<ah-chippingLocation:" . $location . ">"; // Assuming local names
                        }, $currentSecondaryFilters[$prop]);
                        $filterConditions[] = " ?subject ah:" . $prop . " IN (" . implode(", ", $locations) . ") ";
                    }
                }
                // Combine all filter conditions into a SPARQL block
                $filterString = "";
                if (!empty($filterConditions)) {
                    $filterString = implode("\n", $filterConditions) . "\n";
                }

                // Inject the filters before the BIND statement in the SPARQL
                $insertPos = strpos($sparqlQuery, 'BIND(rdf:type AS ?predicate) .');
                if ($insertPos !== false) {
                    $sparqlQuery = substr_replace($sparqlQuery, $filterString, $insertPos, 0);
                }
                
            }
            error_log($sparqlQuery, 3, 'logs/query2.log'); // Log the final query
            break;

        case 'axes':
            $sparqlQuery = "
                
            ";
            // Add secondary filters for axes here if applicable
            break;

        case 'excavations':
            $sparqlQuery = "
                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                PREFIX crmarchaeo: <http://www.cidoc-crm.org/extensions/crmarchaeo/>
                PREFIX dct: <http://purl.org/dc/terms/>

                SELECT ?subject ?predicate ?object
                WHERE {
                    ?subject rdf:type crmarchaeo:A9_Archaeological_Excavation .
                    BIND(rdf:type AS ?predicate) .
                    ?subject dct:identifier ?object .
                }
            ";
            // Add secondary filters for excavations here if applicable
            break;

        case 'archaeologists':
            $sparqlQuery = "
                PREFIX excav: <https://purl.org/ah/ms/excavationMS#>
                PREFIX foaf: <http://xmlns.com/foaf/0.1/>

                SELECT ?subject ?predicate ?object
                WHERE {
                    ?subject a excav:Archaeologist ;
                             foaf:name ?object .
                    BIND(foaf:name AS ?predicate)
                }
            ";
            // Add secondary filters for archaeologists here if applicable
            break;
        default: // No specific subject filter selected (All)
            $sparqlQuery = "
                PREFIX ah: <http://www.purl.com/ah/ms/ahMS#>
                PREFIX excav: <https://purl.org/ah/ms/excavationMS#>
                PREFIX dct: <http://purl.org/dc/terms/>
                PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                PREFIX crmarchaeo: <http://www.cidoc-crm.org/extensions/crmarchaeo/>
                PREFIX dcterms: <http://purl.org/dc/terms/>
                PREFIX crmsci: <http://cidoc-crm.org/extensions/crmsci/>
                PREFIX crm: <http://www.cidoc-crm.org/cidoc-crm/>

                SELECT ?subject ?predicate ?object WHERE {
                    {
                        ?subject a crm:E24_Physical_Man-Made_Thing .
                        BIND(rdf:type AS ?predicate) .
                        OPTIONAL { ?subject dcterms:identifier ?object . }
                    } UNION {
                        ?subject rdf:type crmarchaeo:A9_Archaeological_Excavation .
                        BIND(rdf:type AS ?predicate) .
                        ?subject dct:identifier ?object .
                    } UNION {
                        ?subject a excav:Archaeologist ;
                                 foaf:name ?object .
                        BIND(foaf:name AS ?predicate)
                    } UNION {
                        ?subject ah:hasId ?object.
                        BIND(ah:hasId AS ?predicate)
                    } UNION {
                        ?subject dct:identifier ?object.
                        FILTER(STRSTARTS(STR(?subject), 'https://purl.org/ah/ms/excavationMS/resource/Excavation'))
                        BIND(dct:identifier AS ?predicate)
                    }
                }
                LIMIT 50
            ";
            break;
    }

    // Configure HTTP request
    $client = $this->httpClient;
    $client->setUri($graphdbEndpoint);
    $client->setHeaders(['Accept' => 'application/sparql-results+json']);
    $client->setParameterGet(['query' => $sparqlQuery]);

    // Execute query
    $client->setMethod('GET');
    $response = $client->send();
    $results = json_decode($response->getBody(), true);

    // Send results to the view
    return new ViewModel([
        'results' => $results,
        'currentSubjectFilter' => $currentSubjectFilter, // Pass the current subject filter to the view
        'currentSecondaryFilters' => $currentSecondaryFilters, // Pass the current secondary filters to the view
    ]);
}

}