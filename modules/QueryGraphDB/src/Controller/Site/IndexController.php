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
    // Get the filter from the URL (if set)
    $filter = $this->params()->fromQuery('filter', 'all'); // Default to 'all'

    // Define GraphDB endpoint
    $graphdbEndpoint = "http://localhost:7200/repositories/arch-project-shacl";

    $sparqlQuery = "
    PREFIX ah: <http://www.purl.com/ah/ms/ahMS#>
    PREFIX excav: <https://purl.org/ah/ms/excavationMS#>
    PREFIX dct: <http://purl.org/dc/terms/>
    PREFIX foaf: <http://xmlns.com/foaf/0.1/>

    SELECT ?subject ?predicate ?object WHERE { 
        {
            ?subject ah:hasId ?object.
            BIND(ah:hasId AS ?predicate)
        } UNION {
            ?subject dct:identifier ?object.
            FILTER(STRSTARTS(STR(?subject), 'https://purl.org/ah/ms/excavationMS/resource/Excavation'))
            BIND(dct:identifier AS ?predicate)
        } UNION {
            ?subject foaf:name ?object.
            BIND(foaf:name AS ?predicate)
        }
    } 
    LIMIT 50
";




    // Modify query based on filter
    switch ($filter) {
        case 'arrowheads':
            $sparqlQuery = "
                PREFIX ah: <http://www.purl.com/ah/ms/ahMS#>
                SELECT ?subject ?predicate ?object WHERE { 
                    ?subject ?predicate ?object.
                    FILTER(STRSTARTS(STR(?subject), 'http://www.purl.com/ah/ms/ahMS#arrowhead'))
                }
                LIMIT 50
            ";
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
            break;

        case 'archaeologists':
            $sparqlQuery = "
            PREFIX excav: <https://purl.org/ah/ms/excavationMS#>
            PREFIX foaf: <http://xmlns.com/foaf/0.1/>
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>

            SELECT ?subject ?predicate ?object
            WHERE {
                ?subject rdf:type excav:Archaeologist.
                BIND(rdf:type AS ?predicate).
                ?subject foaf:name ?object.
            }
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
        'filter' => $filter, // Pass filter to view
    ]);
}

}