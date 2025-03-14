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

    // Define base SPARQL query
    $sparqlQuery = "
        PREFIX ah: <http://www.purl.com/ah/ms/ahMS#>
        SELECT ?subject ?predicate ?object WHERE { 
            ?subject ?predicate ?object.
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
                PREFIX excav: <https://purl.org/ah/ms/excavationMS#>

                SELECT ?subject ?predicate ?object WHERE { 
                    ?subject ?predicate ?object.
                    FILTER(STRSTARTS(STR(?subject), 'https://purl.org/ah/ms/excavationMS/resource/Excavation'))
                }
                LIMIT 50
            ";
            break;

        case 'archaeologists':
            $sparqlQuery = "
               PREFIX excav: <https://purl.org/ah/ms/excavationMS#>

                SELECT ?subject ?predicate ?object WHERE { 
                    ?subject ?predicate ?object.
                    FILTER(STRSTARTS(STR(?subject), 'https://purl.org/ah/ms/excavationMS/resource/Archaeologist'))
                }
                LIMIT 50
            ";
            break;

        case 'countries':
            $sparqlQuery = "
                PREFIX ah: <http://www.purl.com/ah/ms/ahMS#>
                SELECT ?subject ?predicate ?object WHERE { 
                    ?subject ?predicate ?object.
                    FILTER(STRSTARTS(STR(?subject), 'http://www.purl.com/ah/ms/ahMS#country'))
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
        'filter' => $filter, // Pass filter to view
    ]);
}

}