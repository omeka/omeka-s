<?php
namespace AddTriplestore\Controller\Site;

require 'vendor/autoload.php';

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Http\Client;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Stream;
use EasyRdf\Graph;

class IndexController extends AbstractActionController
{
    private $graphdbEndpoint = "http://localhost:7200/repositories/arch-project-shacl/rdf-graphs/service";
    private $graphdbQueryEndpoint = "http://localhost:7200/repositories/arch-project-shacl"; // SPARQL endpoint
    private $dataGraphUri = "http://www.arch-project.com/data";

    public function indexAction()
    {
        $site = $this->currentSite();
        return new ViewModel(['site' => $site]);
    }

    public function uploadAction()
    {
        error_log("uploadAction() called");

        $request = $this->getRequest();
        if (!$request instanceof \Laminas\Http\Request) {
            error_log('Invalid request type');
            throw new \RuntimeException('Expected an instance of Laminas\Http\Request');
        }

        $result = 'No file uploaded.';

        if ($request->isPost()) {
            error_log('Processing file upload');
            $file = $request->getFiles()->file;

            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileType = $file['type'];

                // Determine file type based on extension if MIME type is not recognized
                if (strtolower($fileExtension) === 'ttl' && $fileType !== 'application/x-turtle') {
                    $fileType = 'application/x-turtle';
                    error_log('File type set to application/x-turtle based on extension.');
                }

                if (in_array($fileType, ['application/x-turtle', 'application/xml', 'text/xml'])) {
                    try {
                        if ($fileType === 'application/xml' || $fileType === 'text/xml') {
                            // XML processing
                            $rdfXmlData = $this->xmlParser($file);
                            if ($rdfXmlData === 'Failed to load xsml file') {
                                throw new \Exception('Failed to load xsml file');
                            }
                            if ($rdfXmlData === 'Failed to load xml file') {
                                throw new \Exception('Failed to load xml file');
                            }
                            if ($rdfXmlData === 'Failed to convert xml to rdf xml') {
                                throw new \Exception('Failed to convert xml to rdf xml');
                            }
                            $ttlData = $this->xmlTtlConverter($rdfXmlData);
                            error_log('TTL data: ' . $ttlData, 3, OMEKA_PATH . '/logs/ttl-dddfdata.log');
                            $result = $this->sendToGraphDB($ttlData);
                        } else {
                            // TTL processing
                            $data = file_get_contents($file['tmp_name']);
                            $result = $this->sendToGraphDB($data);
                        }
                    } catch (\Exception $e) {
                        $result = 'Error processing file: ' . $e->getMessage();
                        error_log($result);
                    }
                } else {
                    $result = 'Invalid file type. Please upload a valid .ttl or .xml file.';
                    error_log('Invalid file type: ' . $fileType);
                }
            } else {
                $result = 'File upload error: ' . $file['error'];
                error_log($result);
            }
        }

        error_log('Final result: ' . $result);

        return (new ViewModel(['result' => $result, 'site' => $this->currentSite()]))
            ->setTemplate('add-triplestore/site/index/index');
    }

    public function xmlParser($file)
    {
        // Determine which XSLT to use based on the file content
        $xmlContent = file_get_contents($file['tmp_name']);
        if (strpos($xmlContent, '<item id="AH') !== false) {
            $xsltPath = OMEKA_PATH . '/modules/AddTriplestore/asset/xlst/xlst.xml'; // Arrowhead XSLT
        } elseif (strpos($xmlContent, '<Excavation') !== false) {
            $xsltPath = OMEKA_PATH . '/modules/AddTriplestore/asset/xlst/excavationXlst.xml'; // Excavation XSLT

        } else {
            error_log('Could not determine XML type for XSLT selection.');
            return 'Could not determine XML type'; // Or throw an exception
        }

        // load xsml file
        $xslt = new \DOMDocument();
        // failed to load xsml file
        if (!$xslt->load($xsltPath)) {
            error_log('Failed to load xsml file: ' . $xsltPath);
            return 'Failed to load xsml file';
        }

        // Load the uploaded XML file into a DOMDocument
        $auxFile = new \DOMDocument();
        if (!$auxFile->load($file['tmp_name'])) {
            error_log('Failed to load xml file');
            return 'Failed to load xml file';
        }

        // convert xlm to xlm rdf
        $convert = new \XSLTProcessor();
        $convert->importStylesheet($xslt);
        $rdfXmlConverted = $convert->transformToXML($auxFile);

        // check if conversion fail
        if (!$rdfXmlConverted) {
            error_log('Failed to convert xml to rdf xml');
            return 'Failed to convert xml to rdf xml';
        }

        error_log($rdfXmlConverted, 3, OMEKA_PATH . '/logs/rdf-xml-finsal.log');
        return $rdfXmlConverted;
    }

    public function xmlTtlConverter($rdfXmlData)
{
    error_log('Converting RDF-XML to TTL');

    $rdfGraph = new Graph();
    $rdfGraph->parse($rdfXmlData, 'rdfxml');

    error_log('RDF-XML data loaded into graph');

    $ttlData = $rdfGraph->serialise('turtle');

    error_log('RDF-XML data converted to TTL');

    $ttlData = $this->addPrefixesToTTL($ttlData, [
        'ah' => 'http://www.purl.com/ah/ms/ahMS#',
        'ah-shape' => 'http://www.purl.com/ah/kos/ah-shape/',
        'ah-variant' => 'http://www.purl.com/ah/kos/ah-variant/',
        'ah-base' => 'http://www.purl.com/ah/kos/ah-base/',
        'ah-chippingMode' => 'http://www.purl.com/ah/kos/ah-chippingMode/',
        'ah-chippingDirection' => 'http://www.purl.com/ah/kos/ah-chippingDirection/',
        'ah-chippingDelineation' => 'http://www.purl.com/ah/kos/ah-chippingDelineation/',
        'ah-chippingLocation' => 'http://www.purl.com/ah/kos/ah-chippingLocation/',
        'ah-chippingShape' => 'http://www.purl.com/ah/kos/ah-chippingShape/',
        'crm' => 'http://www.cidoc-crm.org/cidoc-crm/',
        'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'xsd' => 'http://www.w3.org/2001/XMLSchema#',
        'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
        'owl' => 'http://www.w3.org/2002/07/owl#',
        'skos' => 'http://www.w3.org/2004/02/skos/core#',
        'dc' => 'http://purl.org/dc/elements/1.1/',
        'dcterms' => 'http://purl.org/dc/terms/',
        'foaf' => 'http://xmlns.com/foaf/0.1/',
        'ah-vocab' => 'http://www.purl.com/ah/kos#',
        'excav' => 'https://purl.org/ah/ms/excavationMS#', // Corrected namespace
        'dct' => 'http://purl.org/dc/terms/',
        'schema' => 'http://schema.org/',
        'voaf' => 'http://purl.org/vocommons/voaf#',
        'vann' => 'http://purl.org/vocab/vann/',
        'dbo' => 'http://dbpedia.org/ontology/',
        'time' => 'http://www.w3.org/2006/time#',
        'edm' => 'http://www.europeana.eu/schemas/edm#',
        'dul' => 'http://www.ontologydesignpatterns.org/ont/dul/DUL.owl#',
        'crmsci' => 'https://cidoc-crm.org/extensions/crmsci/',
        'crmarchaeo' => 'http://www.cidoc-crm.org/extensions/crmarchaeo/',
        'geo' => 'http://www.w3.org/2003/01/geo/wgs84_pos#',
        'sh' => 'http://www.w3.org/ns/shacl#',
    ]);

    
    $ttlData = preg_replace_callback(
        '/time:inXSDYear "(-?\d+)"\^\^xsd:gYear/',
        function($matches) {
            $year = str_replace('-', '', $matches[1]);
            return 'time:inXSDYear "' . $year . '"^^xsd:gYear';
        },
        $ttlData
    );

    // Fix boolean values
    $ttlData = str_replace('"true"', 'true', $ttlData);
    $ttlData = str_replace('"false"', 'false', $ttlData);
    
    $ttlData = preg_replace_callback(
        '/time:inXSDYear "(-?\d+)"\^\^xsd:gYear/',
        function($matches) {
            $year = str_replace('-', '', $matches[1]);
            return 'time:inXSDYear "' . $year . '"^^xsd:gYear';
        },
        $ttlData
    );

    // Fix the instant URIs to match what the SHACL shapes expect
    //$ttlData = str_replace('excav:Instant_LowerBound_', 'excav:Instant_Lower_', $ttlData);
    //$ttlData = str_replace('excav:Instant_UpperBound_',  'excav:Instant_Upper_', $ttlData);

    
    // Determine if this is excavation data
    if (strpos($ttlData, 'crmarchaeo:A9_Archaeological_Excavation') !== false) {
        $patterns = [
            '/<http:\/\/www\.cidoc-crm\.org\/extensions\/crmarchaeo\/A9_Archaeological_Excavation>/' => 'crmarchaeo:A9_Archaeological_Excavation',
            '/<http:\/\/www\.cidoc-crm\.org\/extensions\/crmarchaeo\/A1_Excavation_Processing_Unit>/' => 'crmarchaeo:A1_Excavation_Processing_Unit',
            '/<http:\/\/www\.cidoc-crm\.org\/extensions\/crmarchaeo\/A2_Stratigraphic_Volume_Unit>/' => 'crmarchaeo:A2_Stratigraphic_Volume_Unit',
            '/<dcterms:identifier>([^<]+)<\/dcterms:identifier>/' => 'dcterms:identifier "$1";',
            '/<dul:hasLocation rdf:resource="([^"]+)"\/>/' => 'dul:hasLocation <$1>;',
            '/<crmarchaeo:A9_Archaeological_Excavation rdf:about="([^"]+)"\/>/' => 'crmarchaeo:A9_Archaeological_Excavation <$1>;',
            '/<excav:ArchaeologistShape rdf:resource="([^"]+)"\/>/' => 'excav:ArchaeologistShape <$1>;',
            '/<excav:hasContext rdf:resource="([^"]+)"\/>/' => 'excav:hasContext <$1>;',
            '/foaf:account "([^"]+)"/' => 'foaf:account "$1"^^xsd:anyURI;',
            '/<foaf:name>([^<]+)<\/foaf:name>/' => 'foaf:name "$1";',
            '/foaf:mbox "([^"]+)"/' => 'foaf:mbox "$1"^^xsd:anyURI',
            '/<excav:hasSVU rdf:resource="([^"]+)"\/>/' => 'excav:hasSVU <$1>;',
            '/<dcterms:description>([^<]+)<\/dcterms:description>/' => 'dcterms:description "$1";',
            '/<excav:hasTimeLine rdf:resource="([^"]+)"\/>/' => 'excav:hasTimeLine <$1>;',
            '/<dbo:informationName>([^<]+)<\/dbo:informationName>/' => 'dbo:informationName "$1";',
            '/excav:Archaeologist /' => 'a excav:Archaeologist;',
            '/excav:excavation_/' => 'a excav:Excavation;',
            '/<excav:hasGPSCoordinates rdf:resource="([^"]+)"\/>/' => 'excav:hasGPSCoordinates <$1>;',
            '/<geo:lat rdf:datatype="[^"]+">([^<]+)<\/geo:lat>/' => 'geo:lat "$1"^^xsd:decimal;',
            '/<geo:long rdf:datatype="[^"]+">([^<]+)<\/geo:long>/' => 'geo:long "$1"^^xsd:decimal;',
            '/<time:hasBeginning rdf:resource="([^"]+)"\/>/' => 'time:hasBeginning <$1>;',
            '/<time:hasEnd rdf:resource="([^"]+)"\/>/' => 'time:hasEnd <$1>;',
            '/<time:inXSDYear rdf:datatype="[^"]+">([^<]+)<\/time:inXSDYear>/' => 'time:inXSDYear "$1"^^xsd:gYear;',
            '/<excav:bc rdf:datatype="[^"]+">([^<]+)<\/excav:bc>/' => 'excav:bc $1;',
            '/<dcterms:date rdf:datatype="[^"]+">([^<]+)<\/dcterms:date>/' => 'dcterms:date "$1"^^xsd:date;',
            '/<dbo:depth rdf:datatype="[^"]+">([^<]+)<\/dbo:depth>/' => 'dbo:depth "$1"^^xsd:decimal;',
            '/<crmsci:O19_encountered_object rdf:resource="([^"]+)"\/>/' => 'crmsci:O19_encountered_object <$1>;',
            '/<dbo:district rdf:resource="([^"]+)"\/>/' => 'dbo:district <$1>;',
            '/<dbo:parish rdf:resource="([^"]+)"\/>/' => 'dbo:parish <$1>;',
            '/\s*rdf:about="([^"]+)"/' => '',
            '/\s*rdf:resource="([^"]+)"/' => '',
            '/\s*rdf:datatype="[^"]+"/' => '',
            '/<\?xml[^>]+\?>/' => '',
            '/<rdf:RDF[^>]*>/' => '',
            '/<\/rdf:RDF>/' => '',
        ];
    } else {
        $patterns = [
            '/<ah:shape>([^<]+)<\/ah:shape>/' => 'ah:shape <ah-shape:$1>;',
            '/<ah:variant>([^<]+)<\/ah:variant>/' => 'ah:variant <ah-variant:$1>;',
            '/<crm:E57_Material>([^<]+)<\/crm:E57_Material>/' => 'crm:E57_Material <$1>;',
            '/<ah:foundInCoordinates rdf:resource="([^"]+)"\/>/' => 'ah:foundInCoordinates <$1>;',
            '/<ah:hasMorphology rdf:resource="([^"]+)"\/>/' => 'ah:hasMorphology <$1>;',
            '/<ah:hasTypometry rdf:resource="([^"]+)"\/>/' => 'ah:hasTypometry <$1>;',
            '/<ah:point>([^<]+)<\/ah:point>/' => 'ah:point "$1";',
            '/<ah:body>([^<]+)<\/ah:body>/' => 'ah:body "$1";',
            '/<ah:base>([^<]+)<\/ah:base>/' => 'ah:base <ah-base:$1>;',
            '/<crm:E54_Dimension>([^<]+)<\/crm:E54_Dimension>/' => 'crm:E54_Dimension "$1"^^xsd:decimal;',
            '/<ah:hasChipping rdf:resource="([^"]+)"\/>/' => 'ah:hasChipping <$1>;',
            '/<ah:mode>([^<]+)<\/ah:mode>/' => 'ah:mode <ah-chippingMode:$1>;',
            '/<ah:amplitude>([^<]+)<\/ah:amplitude>/' => 'ah:amplitude "$1";',
            '/<ah:direction>([^<]+)<\/ah:direction>/' => 'ah:direction <ah-chippingDirection:$1>;',
            '/<ah:orientation>([^<]+)<\/ah:orientation>/' => 'ah:orientation "$1";',
            '/<ah:dileneation>([^<]+)<\/ah:dileneation>/' => 'ah:dileneation <ah-chippingDelineation:$1>;',
            '/<ah:chippinglocation-Lateral>([^<]+)<\/ah:chippinglocation-Lateral>/' => 'ah:chippinglocation-Lateral <ah-chippingLocation:$1>;',
            '/<ah:chippingLocation-Transversal>([^<]+)<\/ah:chippingLocation-Transversal>/' => 'ah:chippingLocation-Transversal <ah-chippingLocation:$1>;',
            '/<ah:chippingShape>([^<]+)<\/ah:chippingShape>/' => 'ah:chippingShape <ah-chippingShape:$1>;',
            '/<dcterms:identifier>([^<]+)<\/dcterms:identifier>/' => 'dcterms:identifier "$1";',
            '/<edm:Webresource>([^<]+)<\/edm:Webresource>/' => 'edm:Webresource <$1>;',
            '/<dbo:Annotation>([^<]+)<\/dbo:Annotation>/' => 'dbo:Annotation "$1";',
            '/<crm:E3_Condition_State>([^<]+)<\/crm:E3_Condition_State>/' => 'crm:E3_Condition_State "$1";',
            '/<crm:E55_Type>([^<]+)<\/crm:E55_Type>/' => 'crm:E55_Type "$1";',
            '/<geo:lat>([^<]+)<\/geo:lat>/' => 'geo:lat "$1"^^xsd:decimal;',
            '/<geo:long>([^<]+)<\/geo:long>/' => 'geo:long "$1"^^xsd:decimal;',
        ];
    }
    foreach ($patterns as $pattern => $replacement) {
        $ttlData = preg_replace($pattern, $replacement, $ttlData);
    }


    // Clean up any empty lines or extra spaces
    $ttlData = preg_replace("/\n\s*\n/", "\n", $ttlData);
    $ttlData = trim($ttlData);

    error_log("Cleaned TTL: " . $ttlData, 3, OMEKA_PATH . '/logs/cleaned-ttl.log');
    return $ttlData;
}

    private function addPrefixesToTTL($ttlData, $prefixes)
    {
        $prefixLines = '';
        foreach ($prefixes as $prefix => $iri) {
            $prefixLines .= "@prefix $prefix: <$iri>.\n";
            // log here
            error_log("Adding prefix: $prefix: <$iri>");
        }
        return $prefixLines . $ttlData;
    }

    private function sendToGraphDB($data)
    {
        $logger = new Logger();
        $writer = new Stream(OMEKA_PATH . '/logs/graphdb-errors.log');
        $logger->addWriter($writer);

        try {
            $graphUri = $this->dataGraphUri;

            $validationResult = $this->validateData($data, $graphUri);
            // log the validation result
            error_log('Validation Result: ' . implode('; ', $validationResult));

            if (!empty($validationResult)) {
                $errorMessage = 'Data upload failed: SHACL validation errors: ' . implode('; ', $validationResult);
                error_log($errorMessage);
                $logger->err($errorMessage);
                return $errorMessage;
            }

            // 2. Upload ONLY if validation passes
            $client = new Client();
            $fullUrl = $this->graphdbEndpoint . '?graph=' . urlencode($graphUri);
            $client->setUri($fullUrl);
            $client->setMethod('POST');
            $client->setHeaders(['Content-Type' => 'text/turtle']);
            $client->setRawBody($data);

            $client->setOptions(['timeout' => 60]); // Adjust the timeout as needed

            $response = $client->send();

            $status = $response->getStatusCode();
            $body = $response->getBody();
            $message = "Response Status: $status | Response Body: $body";
            error_log($message);
            $logger->info($message);

            if ($response->isSuccess()) {
                return 'Data uploaded and validated successfully.';
            } else {
                $errorMessage = 'Failed to upload data: ' . $message;
                error_log($errorMessage);
                $logger->err($errorMessage);
                return $errorMessage;
            }
        } catch (\Exception $e) {
            $errorMessage = 'Failed to upload data due to an exception: ' . $e->getMessage();
            $logger->err($errorMessage);
            error_log($errorMessage);
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