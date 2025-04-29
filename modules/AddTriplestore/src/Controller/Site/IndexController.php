<?php

namespace AddTriplestore\Controller\Site;

require 'vendor/autoload.php';

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Http\Client;
use Laminas\Log\Logger;
use Laminas\Log\Writer\Stream;
use EasyRdf\Graph;
use Laminas\Form\FormInterface;
use Laminas\Router\RouteStackInterface;

class IndexController extends AbstractActionController
{
    private $graphdbEndpoint = "http://localhost:7200/repositories/arch-project-shacl/rdf-graphs/service";
    private $graphdbQueryEndpoint = "http://localhost:7200/repositories/arch-project-shacl";
    private $dataGraphUri = "http://www.arch-project.com/";
    private $router;
    private $httpClient;
    private $excavationIdentifier = "0/";

    public function __construct(RouteStackInterface $router, Client $httpClient)
    {
        $this->router = $router;
        $this->httpClient = $httpClient;
    }

    public function indexAction()
    {
        $site = $this->currentSite();
        return new ViewModel(['site' => $site]);
    }

    public function uploadAction()
    {
        error_log("uploadAction() called", 3, OMEKA_PATH . '/logs/upload.log');
    
        $request = $this->getRequest();
        if (!$request instanceof \Laminas\Http\Request) {
            error_log('Invalid request type', 3, OMEKA_PATH . '/logs/upload.log');
            throw new \RuntimeException('Expected an instance of Laminas\Http\Request');
        }
    
        $uploadType = $request->getPost('upload_type') ?: $request->getQuery('upload_type');
        $itemSetId = $request->getPost('item_set_id') ?: $request->getQuery('item_set_id');
        $continuousUpload = $request->getPost('continuous_upload') ?: $request->getQuery('continuous_upload');
    
        error_log('Upload Type: ' . $uploadType . ', Item Set ID: ' . $itemSetId . ', Continuous: ' . $continuousUpload, 3, OMEKA_PATH . '/logs/upload.log');
        
        $result = 'No data received.';
        $ttlData = '';
        $excavationItemSetId = null;
    
        if ($request->isPost() || $request->isGet()) {
            error_log('Processing upload request', 3, OMEKA_PATH . '/logs/upload.log');
    
            try {
                // Handle File Upload
                if ($request->getFiles()->count() > 0 && 
                    isset($request->getFiles()->file) && 
                    $request->getFiles()->file['error'] === UPLOAD_ERR_OK) {
                    
                    error_log('File upload detected', 3, OMEKA_PATH . '/logs/upload.log');
                    $result = $this->processFileUpload($request, $uploadType, $itemSetId);
                    error_log('File upload result: ' . $result, 3, OMEKA_PATH . '/logs/upload.log');
                    
                    // Check if this is an excavation upload and extract the item set ID
                    if ($uploadType === 'excavation' && strpos($result, 'Created Item Set #') !== false) {
                        preg_match('/Created Item Set #(\d+)/', $result, $matches);
                        if (isset($matches[1])) {
                            $excavationItemSetId = $matches[1];
                            error_log('Extracted Item Set ID: ' . $excavationItemSetId, 3, OMEKA_PATH . '/logs/upload.log');
                        }
                    }
                }
                // Handle Form Submission
                elseif ($uploadType) {
                    error_log('Form submission detected', 3, OMEKA_PATH . '/logs/upload.log');
                    $result = $this->processFormSubmission($request, $uploadType, $itemSetId);
                    error_log('Form submission result: ' . $result, 3, OMEKA_PATH . '/logs/upload.log');
                    
                    // Similar check for form submissions
                    if ($uploadType === 'excavation' && strpos($result, 'Created Item Set #') !== false) {
                        preg_match('/Created Item Set #(\d+)/', $result, $matches);
                        if (isset($matches[1])) {
                            $excavationItemSetId = $matches[1];
                            error_log('Extracted Item Set ID from form: ' . $excavationItemSetId, 3, OMEKA_PATH . '/logs/upload.log');
                        }
                    }
                }
                else {
                    $result = 'No file uploaded or form data received.';
                    error_log($result, 3, OMEKA_PATH . '/logs/upload.log');
                }
    
            } catch (\Exception $e) {
                $result = 'Error during upload processing: ' . $e->getMessage();
                error_log($result, 3, OMEKA_PATH . '/logs/upload.log');
                error_log('Exception stack trace: ' . $e->getTraceAsString(), 3, OMEKA_PATH . '/logs/upload.log');
            }
        }
    
        error_log('Final result: ' . $result, 3, OMEKA_PATH . '/logs/upload.log');
        
        // Prepare view variables
        $viewVars = [
            'result' => $result, 
            'site' => $this->currentSite()
        ];
        
        // Add excavation ID if available
        if ($excavationItemSetId) {
            $viewVars['excavationItemSetId'] = $excavationItemSetId;
        }
        
        // For continuous upload (arrowhead uploaded after excavation)
        if ($continuousUpload && $itemSetId) {
            $viewVars['continuousUpload'] = true;
            $viewVars['itemSetId'] = $itemSetId;
        }
        
        return (new ViewModel($viewVars))
            ->setTemplate('add-triplestore/site/index/index');
    }


    private function getCollectingForm(): FormInterface
    {
        try {
            $collectingFormRepresentation = $this->getCollectingFormRepresentation(1); // Adjust form ID as needed
            $collectingForm = $collectingFormRepresentation->getForm();
            $this->modifyCollectingFormAction($collectingForm); // Ensure correct form action
            return $collectingForm;
        } catch (\Exception $e) {
            // Log the error
            error_log('Error getting Collecting form: ' . $e->getMessage());
            // Return a simple form or null to avoid crashing the page
            return new \Laminas\Form\Form('error-form'); // Or return null;
        }
    }

    private function getCollectingFormRepresentation(int $formId)
    {
        return $this->getServiceLocator()->get('Omeka\ApiManager')
            ->read('collecting_forms', $formId)
            ->getContent();
    }

    private function modifyCollectingFormAction(FormInterface $collectingForm): void
    {
        $uploadUrl = $this->router->assemble(
            ['site-slug' => $this->currentSite()->slug()],
            ['name' => 'site/add-triplestore/upload', 'only_uri' => true]
        );
        $collectingForm->setAttribute('action', $uploadUrl);
    }

    private function processFileUpload($request, ?string $uploadType, ?int $itemSetId): string
{
    $file = $request->getFiles()->file;
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileType = $file['type'];

    error_log("Processing file upload: {$file['name']} ({$fileType})", 3, OMEKA_PATH . '/logs/file-upload.log');

    // Handle various MIME types for Turtle and XML files
    if (strtolower($fileExtension) === 'ttl' && $fileType !== 'application/x-turtle') {
        $fileType = 'application/x-turtle';
    }
    
    if ((strtolower($fileExtension) === 'xml' || strtolower($fileExtension) === 'rdf') && 
        !in_array($fileType, ['application/xml', 'text/xml', 'application/rdf+xml'])) {
        $fileType = 'application/xml';
    }

    if (!in_array($fileType, ['application/x-turtle', 'application/xml', 'text/xml', 'application/rdf+xml'])) {
        error_log("Invalid file type: {$fileType}", 3, OMEKA_PATH . '/logs/file-upload.log');
        return 'Invalid file type. Please upload a valid .ttl or .xml file.';
    }

    try {
        if (in_array($fileType, ['application/xml', 'text/xml', 'application/rdf+xml'])) {
            // For XML files, first convert to RDF/XML using XSLT
            error_log("Processing XML file: {$file['name']}", 3, OMEKA_PATH . '/logs/file-upload.log');
            $rdfXmlData = $this->xmlParser($file);
            
            if (is_string($rdfXmlData) && strpos($rdfXmlData, 'Failed') === false) {
                // Now convert the RDF/XML to Turtle
                error_log("Converting RDF/XML to Turtle", 3, OMEKA_PATH . '/logs/file-upload.log');
                $ttlData = $this->xmlTtlConverter($rdfXmlData);
                
                if (!$ttlData) {
                    error_log("Failed to convert RDF/XML to TTL", 3, OMEKA_PATH . '/logs/file-upload.log');
                    throw new \Exception('Failed to convert XML to Turtle format');
                }
            } else {
                error_log("XML Parser error: {$rdfXmlData}", 3, OMEKA_PATH . '/logs/file-upload.log');
                throw new \Exception('Failed to process XML file: ' . $rdfXmlData);
            }
        } else {
            // For Turtle files, just read the content
            error_log("Reading TTL file directly", 3, OMEKA_PATH . '/logs/file-upload.log');
            $ttlData = file_get_contents($file['tmp_name']);
        }

        // Validate the upload type against the data
        error_log("Validating upload type: {$uploadType}", 3, OMEKA_PATH . '/logs/file-upload.log');
        $this->validateUploadType($ttlData, $uploadType);
        
        // Upload the TTL data
        error_log("Uploading TTL data", 3, OMEKA_PATH . '/logs/file-upload.log');
        $uploadResponse = $this->uploadTtlData($ttlData, $itemSetId);
        
        error_log("Upload response: {$uploadResponse}", 3, OMEKA_PATH . '/logs/file-upload.log');
        return $uploadResponse;

    } catch (\Exception $e) {
        error_log("Exception in processFileUpload: " . $e->getMessage(), 3, OMEKA_PATH . '/logs/file-upload.log');
        error_log("Stack trace: " . $e->getTraceAsString(), 3, OMEKA_PATH . '/logs/file-upload.log');
        return 'Error processing file: ' . $e->getMessage();
    }
}



private function processFormSubmission($request, ?string $uploadType, ?int $itemSetId): string
{
    $formData = $request->getQuery('form_data'); // Get form data from query
    $formData = is_array($formData) ? $formData : []; // Ensure it's an array
    error_log('Collecting Form Data (GET): ' . print_r($formData, true), 3, OMEKA_PATH . '/logs/form-data.log');

    try {
        $ttlData = $this->transformCollectingFormDataToTTL($formData, $uploadType);
        if ($ttlData) {
            return $this->uploadTtlData($ttlData, $itemSetId); // Pass itemSetId
        } else {
            return 'Error: Could not transform form data to TTL.';
        }
    } catch (\Exception $e) {
        return 'Error processing form data: ' . $e->getMessage();
    }
}


private function transformCollectingFormDataToTTL(array $formData, ?string $uploadType): ?string
{
    $ttl = '';
    $baseUri = $this->dataGraphUri . $this->excavationIdentifier;

    if ($uploadType === 'arrowhead') {
        $ttl .= "@prefix ah: <http://www.purl.com/ah/ms/ahMS#> .\n";
        $ttl .= "@prefix dcterms: <http://purl.org/dc/terms/> .\n";

        //  *** ADAPT THIS SECTION TO YOUR ARROWHEAD FORM  ***
        //  Use error_log(print_r($formData, true), 3, OMEKA_PATH . '/logs/form-data.log');
        //  to inspect the $formData and adjust the field names accordingly
        if (isset($formData['prompt_1'])) { // Example: 'prompt_1' is a field name
            $ttl .= "    ah:shape \"{$formData['prompt_1']}\" ;\n";
        }
        if (isset($formData['prompt_2'])) {
            $ttl .= "    dcterms:identifier \"{$formData['prompt_2']}\" ;\n";
        }
        //  ...  Map other fields ...

        $ttl .= "    .\n";

    } elseif ($uploadType === 'excavation') {
        $ttl .= "@prefix excav: <https://purl.org/ah/ms/excavationMS#> .\n";
        $ttl .= "@prefix dcterms: <http://purl.org/dc/terms/> .\n";
        $ttl .= "@prefix crmarchaeo: <http://www.cidoc-crm.org/extensions/crmarchaeo/> .\n";

        //  *** ADAPT THIS SECTION TO YOUR EXCAVATION FORM  ***
        //  Use error_log(print_r($formData, true), 3, OMEKA_PATH . '/logs/form-data.log');
        //  to inspect the $formData and adjust the field names accordingly
        if (isset($formData['prompt_3'])) {
            $ttl .= "    dcterms:title \"{$formData['prompt_3']}\" ;\n";
        }
        if (isset($formData['prompt_4'])) {
            $ttl .= "    dcterms:description \"{$formData['prompt_4']}\" ;\n";
        }
        //  ...  Map other fields ...

        $ttl .= "    .\n";
    }

    return !empty(trim($ttl)) ? $ttl : null;
}


private function uploadTtlData(string $ttlData, ?int $itemSetId): string {
    // Check if this is excavation data
    $isExcavation = strpos($ttlData, 'crmarchaeo:A9_Archaeological_Excavation') !== false;
    $excavationIdentifier = null;
    
    // If it's excavation data and no itemSetId is provided, create an item set
    if ($isExcavation && !$itemSetId) {
        // Extract excavation identifier/acronym
        $excavationIdentifier = $this->extractExcavationIdentifier($ttlData);
        error_log('Extracted excavation identifier: ' . $excavationIdentifier, 3, OMEKA_PATH . '/logs/excavation-debug.log');
        
        if ($excavationIdentifier) {
            try {
                // Create a new item set directly using the API manager
                $response = $this->api()->create('item_sets', [
                    'dcterms:title' => [
                        [
                            'type' => 'literal',
                            'property_id' => 1,
                            '@value' => "Excavation $excavationIdentifier"
                        ]
                    ],
                    'dcterms:description' => [
                        [
                            'type' => 'literal',
                            'property_id' => 4,
                            '@value' => "Item set for excavation $excavationIdentifier containing all related findings"
                        ]
                    ],
                    'o:is_public' => true
                ]);
                
                // If successful, get the new item set ID
                if ($response) {
                    $newItemSet = $response->getContent();
                    $itemSetId = $newItemSet->id();
                    error_log('Successfully created item set with ID: ' . $itemSetId, 3, OMEKA_PATH . '/logs/excavation-debug.log');
                } else {
                    error_log('Empty response when creating item set', 3, OMEKA_PATH . '/logs/excavation-debug.log');
                }
            } catch (\Exception $e) {
                error_log('Error creating item set: ' . $e->getMessage(), 3, OMEKA_PATH . '/logs/excavation-debug.log');
            }
        }
    }
    
    // Now proceed with the regular upload process
    // First, upload to GraphDB
    $graphDbResult = $this->sendToGraphDB($ttlData);
    
    if (strpos($graphDbResult, 'successfully') !== false) {
        // If GraphDB upload is successful, then process in Omeka S
        $omekaResult = $this->transformTtlToOmekaSData($ttlData, $itemSetId);
        $omekaResponse = $this->sendToOmekaS($omekaResult);
        
        if (empty($omekaResponse['errors'])) {
            $createdItems = $omekaResponse['created_items'];
            $updatedCount = 0;
            
            foreach ($createdItems as $item) {
                $itemId = $item['o:id']; // Get the Omeka assigned ID
                
                // Update titles based on content type
                if ($isExcavation) {
                    $title = "Excavation $excavationIdentifier Item $itemId";
                } else {
                    $title = "Arrowhead $itemId";
                }
                
                // Update the title with the Omeka ID
                try {
                    $updateResult = $this->api()->update('items', $itemId, [
                        'dcterms:title' => [
                            [
                                'type' => 'literal',
                                'property_id' => 1,
                                '@value' => $title
                            ]
                        ]
                    ], [], ['isPartial' => true]);
                    
                    if ($updateResult) {
                        $updatedCount++;
                    }
                } catch (\Exception $e) {
                    error_log('Error updating item title: ' . $e->getMessage(), 3, OMEKA_PATH . '/logs/excavation-debug.log');
                }
            }
            
            if ($isExcavation && $itemSetId) {
                return "Data uploaded successfully to both GraphDB and Omeka S. Created Item Set #{$itemSetId} for excavation '{$excavationIdentifier}' and " . 
                      count($createdItems) . " items with updated titles.";
            } else {
                return 'Data uploaded successfully to both GraphDB and Omeka S. Created ' . 
                      count($createdItems) . ' items with updated titles.';
            }
        } else {
            return 'Data uploaded to GraphDB, but Omeka S errors: ' . 
                  implode('; ', $omekaResponse['errors']);
        }
    } else {
        return 'Failed to upload data to GraphDB: ' . $graphDbResult;
    }
}

/**
 * Extract the excavation identifier from TTL data
 * 
 * @param string $ttlData
 * @return string|null
 */
/**
 * Extract the excavation identifier from TTL data
 * 
 * @param string $ttlData
 * @return string|null
 */
private function extractExcavationIdentifier(string $ttlData): ?string
{
    error_log("Extracting excavation identifier from TTL data", 3, OMEKA_PATH . '/logs/excavation-identifier.log');
    
    // Save TTL data for debugging
    file_put_contents(OMEKA_PATH . '/logs/ttl-data-for-id.log', $ttlData);
    
    // Patterns to try for finding identifiers
    $patterns = [
        // Look for dcterms:identifier with quotes and datatype
        '/(?:dcterms|dct):identifier\s+"([^"]+)"(?:\^\^xsd:string)?/',
        
        // Look for dcterms:identifier with angle brackets (triple format)
        '/<(?:http:\/\/purl\.org\/dc\/terms\/|http:\/\/purl\.org\/dc\/elements\/1\.1\/)identifier>[^<]*<\/(?:http:\/\/purl\.org\/dc\/terms\/|http:\/\/purl\.org\/dc\/elements\/1\.1\/)identifier>/',
        
        // Look for Excavation resource identifier in URI 
        '/<https?:\/\/[^\/]+\/(?:ah\/ms\/excavationMS\/resource\/|)Excavation[_-]([^>]+)>/',
        
        // Look for Acronym property
        '/(?:excav:|http:\/\/[^\/]+\/excavationMS#)Acronym[^"]*"([^"]+)"/',
    ];
    
    // Try each pattern
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $ttlData, $matches)) {
            $identifier = isset($matches[1]) ? $matches[1] : $matches[0];
            
            // Clean up the identifier if needed
            $identifier = preg_replace('/[^a-zA-Z0-9_-]/', '', $identifier);
            
            error_log("Found excavation identifier: {$identifier} using pattern: {$pattern}", 3, OMEKA_PATH . '/logs/excavation-identifier.log');
            return $identifier;
        }
    }
    
    // Extract the first part from the first triple as a fallback
    if (preg_match('/<([^>]+)>/', $ttlData, $matches)) {
        $uri = $matches[1];
        $parts = explode('/', $uri);
        $lastPart = end($parts);
        
        error_log("Fallback identifier from URI: {$lastPart}", 3, OMEKA_PATH . '/logs/excavation-identifier.log');
        return $lastPart;
    }
    
    // If we can't find a suitable identifier, generate one based on timestamp
    $generatedId = 'EXC' . time();
    error_log("Generated fallback excavation identifier: {$generatedId}", 3, OMEKA_PATH . '/logs/excavation-identifier.log');
    return $generatedId;
}

private function processOmekaS(string $ttlData, ?int $itemSetId): string
{
    $omekaData = $this->transformTtlToOmekaSData($ttlData, $itemSetId); // Pass itemSetId
    error_log('Omeka Data: ' . print_r($omekaData, true), 3, OMEKA_PATH . '/logs/omeka-data-2.log');
    $omekaErrors = $this->sendToOmekaS($omekaData);
    return implode('; ', $omekaErrors);
}


private function validateUploadType(string $ttlData, ?string $uploadType): void
{
    if (!$uploadType) {
        return; // No upload type specified, skip validation
    }

    $isExcavation = strpos($ttlData, 'crmarchaeo:A9_Archaeological_Excavation') !== false;
    if ($uploadType === 'excavation' && !$isExcavation) {
        throw new \Exception('Invalid data type for excavation upload.');
    } elseif ($uploadType === 'arrowhead' && $isExcavation) {
        throw new \Exception('Invalid data type for Arrowhead upload.');
    }
}

public function xmlParser($file)
{
    error_log('Starting XML parser for file: ' . $file['name'], 3, OMEKA_PATH . '/logs/xml-debug.log');
    
    // Read file content
    $xmlContent = file_get_contents($file['tmp_name']);
    if (!$xmlContent) {
        error_log('Failed to read XML file content', 3, OMEKA_PATH . '/logs/xml-debug.log');
        return 'Failed to read XML file content';
    }
    
    error_log('XML content length: ' . strlen($xmlContent), 3, OMEKA_PATH . '/logs/xml-debug.log');
    
    // Save raw XML for debugging
    file_put_contents(OMEKA_PATH . '/logs/original-xml.log', $xmlContent);
    
    // Determine XSLT type based on content
    $xsltPath = null;
    if (strpos($xmlContent, '<item id="AH') !== false) {
        $xsltPath = OMEKA_PATH . '/modules/AddTriplestore/asset/xlst/xlst.xml';
        error_log('Detected Arrowhead XML, using xlst.xml', 3, OMEKA_PATH . '/logs/xml-debug.log');
    } elseif (strpos($xmlContent, '<Excavation') !== false) {
        $xsltPath = OMEKA_PATH . '/modules/AddTriplestore/asset/xlst/excavationXlst.xml';
        error_log('Detected Excavation XML, using excavationXlst.xml', 3, OMEKA_PATH . '/logs/xml-debug.log');
    } else {
        error_log('Could not determine XML type. XML starts with: ' . substr($xmlContent, 0, 100), 3, OMEKA_PATH . '/logs/xml-debug.log');
        return 'Could not determine XML type. File does not contain expected markers.';
    }
    
    // Check if XSLT file exists
    if (!file_exists($xsltPath)) {
        error_log('XSLT file does not exist: ' . $xsltPath, 3, OMEKA_PATH . '/logs/xml-debug.log');
        return 'Failed to find XSLT file: ' . $xsltPath;
    }
    
    try {
        // Load XSLT file
        $xslt = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loadResult = $xslt->load($xsltPath);
        if (!$loadResult) {
            $errors = libxml_get_errors();
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = 'Line ' . $error->line . ': ' . $error->message;
            }
            libxml_clear_errors();
            error_log('Failed to load XSLT file: ' . implode('; ', $errorMessages), 3, OMEKA_PATH . '/logs/xml-debug.log');
            return 'Failed to load XSLT file';
        }
        
        // Load XML from string instead of file
        $auxFile = new \DOMDocument();
        $loadResult = $auxFile->loadXML($xmlContent);
        if (!$loadResult) {
            $errors = libxml_get_errors();
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = 'Line ' . $error->line . ': ' . $error->message;
            }
            libxml_clear_errors();
            error_log('Failed to load XML. Errors: ' . implode('; ', $errorMessages), 3, OMEKA_PATH . '/logs/xml-debug.log');
            return 'Failed to load XML file. XML parsing errors.';
        }
        
        // Convert XML to RDF/XML
        $convert = new \XSLTProcessor();
        $importResult = $convert->importStylesheet($xslt);
        if (!$importResult) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            error_log('Failed to import stylesheet: ' . implode('; ', $errors), 3, OMEKA_PATH . '/logs/xml-debug.log');
            return 'Failed to import XSLT stylesheet';
        }
        
        $rdfXmlConverted = $convert->transformToXML($auxFile);
        if (!$rdfXmlConverted) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            error_log('Failed to transform XML to RDF/XML: ' . implode('; ', $errors), 3, OMEKA_PATH . '/logs/xml-debug.log');
            return 'Failed to convert XML to RDF/XML';
        }
        
        // Save the RDF/XML output for debugging
        file_put_contents(OMEKA_PATH . '/logs/rdf-xml-output.log', $rdfXmlConverted);
        
        error_log('XML transformation successful. RDF/XML length: ' . strlen($rdfXmlConverted), 3, OMEKA_PATH . '/logs/xml-debug.log');
        return $rdfXmlConverted;
    } catch (\Exception $e) {
        error_log('Exception in XML parsing: ' . $e->getMessage(), 3, OMEKA_PATH . '/logs/xml-debug.log');
        return 'Failed to parse XML: ' . $e->getMessage();
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors(false);
    }
}

public function xmlTtlConverter($rdfXmlData)
{
    error_log('Starting RDF/XML to TTL conversion', 3, OMEKA_PATH . '/logs/ttl-debug.log');
    
    if (empty($rdfXmlData)) {
        error_log('Empty RDF/XML data received', 3, OMEKA_PATH . '/logs/ttl-debug.log');
        return null;
    }
    
    // Save input for debugging
    file_put_contents(OMEKA_PATH . '/logs/rdf-xml-input.log', $rdfXmlData);
    
    try {
        // Suppress deprecated warnings temporarily
        error_reporting(E_ALL & ~E_DEPRECATED);
        
        $rdfGraph = new \EasyRdf\Graph();
        $rdfGraph->parse($rdfXmlData, 'rdfxml');
        
        // Restore error reporting
        error_reporting(E_ALL);
        
        error_log('RDF/XML data loaded into graph successfully', 3, OMEKA_PATH . '/logs/ttl-debug.log');
        
        $ttlData = $rdfGraph->serialise('turtle');
        
        if (empty($ttlData)) {
            error_log('Failed to serialize graph to Turtle', 3, OMEKA_PATH . '/logs/ttl-debug.log');
            return null;
        }
        
        // Save serialized TTL for debugging
        file_put_contents(OMEKA_PATH . '/logs/ttl-before-prefixes.log', $ttlData);
        
        error_log('Graph serialized to Turtle successfully', 3, OMEKA_PATH . '/logs/ttl-debug.log');
        
        // Add prefixes and perform cleanup
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
            'excav' => 'https://purl.org/ah/ms/excavationMS#',
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
        
        // Save final TTL
        file_put_contents(OMEKA_PATH . '/logs/final-ttl.log', $ttlData);
        
        error_log('Final TTL data generated successfully', 3, OMEKA_PATH . '/logs/ttl-debug.log');
        return $ttlData;
    } catch (\Exception $e) {
        error_log('Exception in xmlTtlConverter: ' . $e->getMessage(), 3, OMEKA_PATH . '/logs/ttl-debug.log');
        error_log('Stack trace: ' . $e->getTraceAsString(), 3, OMEKA_PATH . '/logs/ttl-debug.log');
        return null;
    }
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

    error_log("Sending data to GraphDB", 3, OMEKA_PATH . '/logs/graphdb.log');
    
    // Check if data is not empty
    if (empty($data)) {
        $errorMessage = 'Cannot send empty data to GraphDB';
        error_log($errorMessage, 3, OMEKA_PATH . '/logs/graphdb.log');
        $logger->err($errorMessage);
        return $errorMessage;
    }
    
    // Save data for debugging
    file_put_contents(OMEKA_PATH . '/logs/data-to-graphdb.log', $data);
    
    // Check if data is excavation data and extract identifier
    if (strpos($data, 'crmarchaeo:A9_Archaeological_Excavation') !== false) {
        // Find and log the excavation identifier
        $identifier = $this->extractExcavationIdentifier($data);
        if ($identifier) {
            $this->excavationIdentifier = $identifier . '/';
            error_log('Excavation Identifier: ' . $this->excavationIdentifier, 3, OMEKA_PATH . '/logs/graphdb.log');
        }
    }
    
    $this->dataGraphUri .= $this->excavationIdentifier;
    error_log('Data Graph URI: ' . $this->dataGraphUri, 3, OMEKA_PATH . '/logs/graphdb.log');

    try {
        $graphUri = $this->dataGraphUri;

        // 1. Validate the data before sending
        error_log("Validating data before sending to GraphDB", 3, OMEKA_PATH . '/logs/graphdb.log');
        $validationResult = $this->validateData($data, $graphUri);
        
        error_log('Validation Result: ' . json_encode($validationResult), 3, OMEKA_PATH . '/logs/graphdb.log');

        if (!empty($validationResult)) {
            $errorMessage = 'Data upload failed: SHACL validation errors: ' . implode('; ', $validationResult);
            error_log($errorMessage, 3, OMEKA_PATH . '/logs/graphdb.log');
            $logger->err($errorMessage);
            return $errorMessage;
        }

        // 2. Upload ONLY if validation passes
        error_log("Sending validated data to GraphDB endpoint: {$this->graphdbEndpoint}", 3, OMEKA_PATH . '/logs/graphdb.log');
        
        $client = new Client();
        $fullUrl = $this->graphdbEndpoint . '?graph=' . urlencode($graphUri);
        $client->setUri($fullUrl);
        $client->setMethod('POST');
        $client->setHeaders(['Content-Type' => 'text/turtle']);
        $client->setRawBody($data);

        $client->setOptions(['timeout' => 60]); // Set generous timeout

        error_log("Request prepared, sending to: {$fullUrl}", 3, OMEKA_PATH . '/logs/graphdb.log');
        $response = $client->send();

        $status = $response->getStatusCode();
        $body = $response->getBody();
        $message = "GraphDB Response Status: $status | Response Body: $body";
        error_log($message, 3, OMEKA_PATH . '/logs/graphdb.log');
        $logger->info($message);

        if ($response->isSuccess()) {
            return 'Data uploaded and validated successfully.';
        } else {
            $errorMessage = 'Failed to upload data: ' . $message;
            error_log($errorMessage, 3, OMEKA_PATH . '/logs/graphdb.log');
            $logger->err($errorMessage);
            return $errorMessage;
        }
    } catch (\Exception $e) {
        $errorMessage = 'Failed to upload data due to an exception: ' . $e->getMessage();
        $logger->err($errorMessage);
        error_log($errorMessage, 3, OMEKA_PATH . '/logs/graphdb.log');
        error_log('Exception stack trace: ' . $e->getTraceAsString(), 3, OMEKA_PATH . '/logs/graphdb.log');
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


    private function transformTtlToOmekaSData($ttlData, $itemSetId): array {
        $graph = new \EasyRdf\Graph();
        $graph->parse($ttlData, 'turtle');
        
        $omekaData = [];
        $rdfData = $graph->toRdfPhp();
        
        // Find arrowhead subjects - these will be our main items
        $arrowheadSubjects = [];
        foreach ($rdfData as $subject => $predicates) {
            foreach ($predicates as $predicate => $objects) {
                foreach ($objects as $object) {
                    if ($predicate === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' && 
                        $object['type'] === 'uri' && 
                        $object['value'] === 'http://www.cidoc-crm.org/cidoc-crm/E24_Physical_Man-Made_Thing') {
                        $arrowheadSubjects[] = $subject;
                    }
                }
            }
        }
        
        // Process each arrowhead as a single item
        foreach ($arrowheadSubjects as $arrowheadSubject) {
            $itemData = [
                'o:resource_class' => ['o:id' => 1], // Default Item Resource Class ID
                'o:item_set' => [],                  // Will be populated if itemSetId exists
            ];
            
            // Add item to item set if provided
            if ($itemSetId) {
                $itemData['o:item_set'][] = ['o:id' => $itemSetId];
            }
            
            // Process the main arrowhead properties
            $this->processSubjectProperties($rdfData, $arrowheadSubject, $itemData);
            
            // Extract identifier for the title
            $identifier = null;
            if (isset($itemData['http://purl.org/dc/terms/identifier'])) {
                foreach ($itemData['http://purl.org/dc/terms/identifier'] as $identifierValue) {
                    if (isset($identifierValue['@value'])) {
                        $identifier = $identifierValue['@value'];
                        break;
                    }
                }
            }
            
            // Set a proper title in Dublin Core terms
            if ($identifier) {
                $itemData['dcterms:title'] = [
                    [
                        'type' => 'literal',
                        'property_id' => 1, // dcterms:title property ID in Omeka
                        '@value' => "Arrowhead"
                    ]
                ];
            } else {
                // Extract subject ID as fallback
                $itemData['dcterms:title'] = [
                    [
                        'type' => 'literal',
                        'property_id' => 1, // dcterms:title property ID in Omeka
                        '@value' => "Arrowhead"
                    ]
                ];
            }
            
            // Now process related subjects (morphology, typometry, chipping, coordinates)
            $this->processRelatedSubjects($rdfData, $arrowheadSubject, $itemData);
            
            $omekaData[] = $itemData;
        }
        
        return $omekaData;
    }
    
    private function processSubjectProperties($rdfData, $subject, &$itemData) {
        if (!isset($rdfData[$subject])) {
            return;
        }
        
        foreach ($rdfData[$subject] as $predicate => $objects) {
            $propertyId = $this->getOmekaPropertyId($predicate);
            
            if ($propertyId) {
                if (!isset($itemData[$predicate])) {
                    $itemData[$predicate] = [];
                }
                
                foreach ($objects as $object) {
                    $value = null;
                    
                    if ($object['type'] === 'literal') {
                        $value = [
                            'type' => 'literal',
                            'property_id' => $propertyId,
                            '@value' => $object['value'],
                        ];
                        if (isset($object['datatype'])) {
                            $value['@type'] = $object['datatype'];
                        }
                        if (isset($object['lang'])) {
                            $value['@language'] = $object['lang'];
                        }
                    } elseif ($object['type'] === 'uri') {
                        // Don't include references to other subjects we'll process separately
                        if (isset($rdfData[$object['value']])) {
                            continue;
                        }
                        
                        // Handle special cases for vocabulary terms
                        if (strpos($object['value'], 'http://www.purl.com/ah/kos/') === 0) {
                            // Extract the term from the URI
                            $parts = explode('/', $object['value']);
                            $term = end($parts);
                            
                            $value = [
                                'type' => 'literal',
                                'property_id' => $propertyId,
                                '@value' => $term,
                            ];
                        } else {
                            $value = [
                                'type' => 'resource',
                                'property_id' => $propertyId,
                                '@id' => $object['value'],
                            ];
                        }
                    }
                    
                    if ($value !== null) {
                        $itemData[$predicate][] = $value;
                    }
                }
            }
        }
    }
    
    private function processRelatedSubjects($rdfData, $mainSubject, &$itemData) {
        if (!isset($rdfData[$mainSubject])) {
            return;
        }
        
        // Find related subjects
        $relatedSubjects = [];
        
        foreach ($rdfData[$mainSubject] as $predicate => $objects) {
            foreach ($objects as $object) {
                if ($object['type'] === 'uri' && isset($rdfData[$object['value']])) {
                    $relatedSubjects[$predicate] = $object['value'];
                }
            }
        }
        
        // Process each related subject
        foreach ($relatedSubjects as $relation => $subject) {
            // Record the property connecting this subject to the main arrowhead
            $propertyId = $this->getOmekaPropertyId($relation);
            if ($propertyId) {
                if (!isset($itemData[$relation])) {
                    $itemData[$relation] = [];
                }
                
                // Now get all properties of the related subject
                $this->processSubjectProperties($rdfData, $subject, $itemData);
                
                // Recursively process any subjects related to this one
                $this->processRelatedSubjects($rdfData, $subject, $itemData);
            }
        }
    }


    private function getOmekaPropertyId($omekaProperty) {
        $propertyIds = [
            'http://purl.org/dc/terms/identifier' => 10,  // dcterms:identifier
            'http://www.europeana.eu/schemas/edm#Webresource' => 100, // Replace with actual ID
            'http://www.purl.com/ah/kos/ah-shape/' => 7460, // Replace with actual ID
            'http://www.cidoc-crm.org/cidoc-crm/P45_consists_of' => 478, // Replace with actual ID
            'http://dbpedia.org/ontology/Annotation' => 57, // Replace with actual ID
            'http://www.cidoc-crm.org/cidoc-crm/E3_Condition_State' => 476, // Replace with actual ID
            'http://www.cidoc-crm.org/cidoc-crm/E55_Type' => 399, // Replace with actual ID 
            'http://www.purl.com/ah/ms/ahMS#variant' => 7461, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#foundInCoordinates' => 7456, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#hasMorphology' => 7457, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#hasTypometry' => 7458, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#point' => 7462, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#body' => 7463, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#base' => 7464, // Replace with actual ID
            'http://www.cidoc-crm.org/cidoc-crm/E54_Dimension' => 474, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#hasChipping' => 7459, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#mode' => 7465, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#amplitude' => 7466, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#direction' => 7467, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#orientation' => 7468, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#delineation' => 7469, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#chippinglocation-Lateral' => 7470, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#chippingLocation-Transversal' => 7471, // Replace with actual ID
            'http://www.purl.com/ah/ms/ahMS#chippingShape' => 7472, // Replace with actual ID
            'http://www.w3.org/2003/01/geo/wgs84_pos#lat' => 257, // Replace with actual ID
            'http://www.w3.org/2003/01/geo/wgs84_pos#long' => 259, // Replace with actual ID
        ];
        
        return $propertyIds[$omekaProperty] ?? null;
    }
    

    private function sendToOmekaS($omekaData) {
        $omekaBaseUrl = 'http://localhost/api';
        $omekaKeyIdentity = '2TGK0xT9tEMCUQs1178OyCnyRcIQpv5B';
        $omekaKeyCredential = '9IFd207Y8D5yG1bmtnCllmbgZweuMfQA';
        $omekaUser = 1;
    
        $client = new Client();
        $client->setMethod('POST');
        $client->setHeaders([
            'Content-Type' => 'application/json',
            'Omeka-S-Api-Key' => $omekaUser,
        ]);
    
        $errors = [];
        $createdItems = [];
        
        foreach ($omekaData as $itemIndex => $itemData) {
            $fullUrl = rtrim($omekaBaseUrl, '/') . '/items' . 
                       '?key_identity=' . urlencode($omekaKeyIdentity) .
                       '&key_credential=' . urlencode($omekaKeyCredential);
            
            $client->setUri($fullUrl);
            $client->setRawBody(json_encode($itemData));
            $response = $client->send();
    
            if (!$response->isSuccess()) {
                $errors[] = 'Failed to create item ' . ($itemIndex + 1) . ': ' . 
                             $response->getStatusCode() . ' - ' . $response->getBody();
                error_log('Omeka S API Error: ' . $response->getBody());
            } else {
                $createdItems[] = json_decode($response->getBody(), true);
                error_log('Omeka S Item Created Successfully: ID=' . 
                           json_decode($response->getBody(), true)['o:id']);
            }
        }
    
        return [
            'errors' => $errors,
            'created_items' => $createdItems
        ];
    }
}
