<?php

namespace SyncData\Controller\Admin; // Corrected namespace

use Omeka\Mvc\Controller\AbstractAdminController;
use Omeka\Permissions\Acl;
use Laminas\View\Model\ViewModel;
use Laminas\Form\Form;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Stream;
use Laminas\Http\Client;
use Laminas\Http\Request;
use PDO;

class IndexController extends AbstractAdminController
{
    private $form;
    private $logger;

    public function __construct(Form $form = null)
    {
        $this->form = $form;
        $this->logger = new Logger();
        $writer = new Stream(OMEKA_PATH . '/logs/sync-data.log'); // Corrected log file name
        $this->logger->addWriter($writer);
    }

    public function indexAction()
    {
        $form = $this->getForm();
        return new ViewModel(['form' => $form]);
    }

    public function syncAction()
    {
        $this->getAcl()->deny(null, null);

        $request = $this->getRequest();
        if (!$request instanceof Request || !$request->isPost()) {
            $this->messenger()->addErrorMessage('Invalid request.');
            return $this->redirect()->toRoute('admin/sync-data'); // Corrected route
        }

        $form = $this->getForm();
        $form->setData($request->getPost());
        if (!$form->isValid()) {
            $this->messenger()->addErrorMessage('Invalid form.');
            return new ViewModel(['form' => $form]);
        }

        $data = $form->getData();
        $graphdb_url = $data['graphdb_url'];
        $graphdb_repository = $data['graphdb_repository'];

        try {
            $this->syncDataToGraphDB($graphdb_url, $graphdb_repository);
            $this->messenger()->addSuccessMessage('Synchronization job has been queued.');
        } catch (\Exception $e) {
            $this->messenger()->addErrorMessage('Synchronization failed: ' . $e->getMessage());
            $this->logger->err('Synchronization failed: ' . $e->getMessage());
        }

        return $this->redirect()->toRoute('admin/sync-data'); // Corrected route
    }

    private function getForm()
    {
        if (!$this->form) {
            $this->form = $this->getServiceLocator()->get('FormElementManager')->get(Form\ConfigForm::class);
        }
        return $this->form;
    }

    private function getDbConnection()
    {
        // Your database connection logic (PDO)
        $host = getenv('OMEKA_DB_HOST');
        $dbname = getenv('OMEKA_DB_NAME');
        $user = getenv('OMEKA_DB_USER');
        $pass = getenv('OMEKA_DB_PASSWORD');

        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    private function getArrowheadData(PDO $conn)
    {
        // Function to fetch arrowhead data from Omeka S
        // Implement your SQL queries here to get the data you need
        // based on the Arrowhead MAP.
        $stmt = $conn->prepare("
            SELECT
                i.id AS item_id,
                i.title,
                -- Add more fields as needed
                e.value AS shape,
                d.value AS description
            FROM
                item i
            LEFT JOIN value e ON e.resource_id = i.id AND e.property_id = (SELECT id FROM property WHERE local_name = 'shape')
            LEFT JOIN value d ON d.resource_id = i.id AND d.property_id = (SELECT id FROM property WHERE local_name = 'description')
            WHERE i.item_set_id = 1;

        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function generateRdf(array $arrowheadData): string
    {
        // Function to generate Turtle RDF from the fetched data
        // Implement your RDF generation logic here,
        // mapping Omeka S fields to RDF properties.
        $turtle = "@prefix rdf:   <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\n";
        $turtle .= "@prefix rdfs:  <http://www.w3.org/2000/01/rdf-schema#> .\n";
        $turtle .= "@prefix xsd:   <http://www.w3.org/2001/XMLSchema#> .\n";
        $turtle .= "@prefix ah:    <http://www.purl.com/ah/ms/ahMS#> .\n";
        $turtle .= "@prefix ah-shape: <http://www.purl.com/ah/kos/ah-shape/> .\n\n";

        foreach ($arrowheadData as $row) {
            $itemUri = "<http://your.omeka.s.site/item/{$row['item_id']}>";
            $turtle .= "$itemUri rdf:type ah:Arrowhead .\n";
            if ($row['title']) {
                $turtle .= "$itemUri rdfs:label \"{$row['title']}\"^^xsd:string .\n";
            }
            if ($row['shape']) {
                $turtle .= "$itemUri ah:shape ah-shape:{$row['shape']} .\n";
            }
            if ($row['description']) {
                $turtle .= "$itemUri dcterms:description \"{$row['description']}\"^^xsd:string .\n";
            }
            // Add more properties based on your Arrowhead MAP
        }

        return $turtle;
    }

    private function validateRdf(string $rdfData): array
    {
        // Implement SHACL validation here (example: using a process call)
        // Return an array of validation errors.
        return []; // Placeholder
    }

    private function sendToGraphDB(string $rdfData, string $graphdb_url, string $graphdb_repository): string
    {
        // Function to send RDF data to GraphDB using cURL
        $client = new Client();
        $fullUrl = "$graphdb_url/repositories/$graphdb_repository/statements";
        $client->setUri($fullUrl);
        $client->setMethod('POST');
        $client->setHeaders(['Content-Type' => 'text/turtle']);
        $client->setRawBody("INSERT DATA { GRAPH <http://your.graphdb.graph/> { $rdfData } }"); // Adjust graph URI

        $response = $client->send();

        if ($response->isSuccess()) {
            return 'Data uploaded to GraphDB.';
        } else {
            throw new \Exception("Failed to upload data to GraphDB: " . $response->getBody());
        }
    }

    public function testAction()
    {
        $this->layout()->setVariable('pageTitle', 'Test GraphDB Connection');
        $form = $this->getForm();
        return new ViewModel(['form' => $form]);
    }
}