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
        // URL do endpoint do GraphDB
        $graphdbEndpoint = "http://localhost:7200/repositories/arch-project-shacl";

        // Query SPARQL
        $sparqlQuery = "
            PREFIX ah: <http://www.purl.com/ah/ms/ahMS#>

            SELECT?subject?predicate?object
                WHERE {
                    ah:arrowhead1?predicate?object.
                    BIND(ah:arrowhead5 AS?subject)  
                }
        ";

        // Configuração da requisição HTTP
        $client = $this->httpClient;
        $client->setUri($graphdbEndpoint);
        $client->setHeaders([
            'Accept' => 'application/sparql-results+json',
        ]);
        $client->setParameterGet(['query' => $sparqlQuery]);

        // Executa a consulta
        $client->setMethod('GET');
        $response = $client->send();

        // Decodifica a resposta JSON
        $results = json_decode($response->getBody(), true);

        // Passa os resultados para a view
        return new ViewModel([
            'results' => $results,
        ]);
    }
}