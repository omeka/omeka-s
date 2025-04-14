<?php

namespace GraphDBSync\Job;

use Omeka\Job\AbstractJob;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Stream;
use PDO;
use Laminas\Http\Client;

class SyncJob extends AbstractJob
{
    public function perform()
    {
        $logger = new Logger();
        $writer = new Stream(OMEKA_PATH . '/logs/graphdb-sync.log');
        $logger->addWriter($writer);

        $this->logger = $logger;

        $job = $this->getJob();
        $graphdb_url = $job->getDataValue('graphdb_url');
        $graphdb_repository = $job->getDataValue('graphdb_repository');

        try {
            $conn = $this->getDbConnection();
            $omeka_data = $this->getArrowheadData($conn);
            $rdf_data = $this->generateRdf($omeka_data);
            $this->validateRdf($rdf_data); // Validate RDF
            $this->sendToGraphDB($rdf_data, $graphdb_url, $graphdb_repository);
            $this->log("Synchronization job completed successfully.");
        } catch (\Exception $e) {
            $this->log("Synchronization job failed: " . $e->getMessage(), Logger::ERR);
        } finally {
            if ($conn) {
                $conn = null; // Close connection
            }
        }
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
        // Your Omeka S data retrieval logic (SQL queries)
        // ...
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
        // Your RDF generation logic
        // ...
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
        // Implement SHACL validation (example: using a process call)
        // ...
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

    private function log(string $message, int $priority = Logger::INFO)
    {
        if ($this->logger) {
            $this->logger->log($priority, $message);
        }
        $this->job->addStatusMessage($message);
    }
}