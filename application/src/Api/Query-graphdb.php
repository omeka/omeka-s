<?php
namespace Omeka\Api;
// Set response headers
header('Content-Type: application/json');

// Check if a query parameter is provided
if (!isset($_GET['query'])) {
    echo json_encode(['error' => 'Missing query parameter']);
    exit;
}

// Get the SPARQL query from the request
$sparqlQuery = $_GET['query'];

// GraphDB SPARQL endpoint
$endpoint = 'http://ec2-13-61-100-218.eu-north-1.compute.amazonaws.com:7200/repositories/arch-project-repository'; // Replace with your repository details

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $endpoint . '?query=' . urlencode($sparqlQuery));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/sparql-results+json',
]);

// Execute the query
$response = curl_exec($ch);

// Handle errors
if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
    curl_close($ch);
    exit;
}

// Close cURL
curl_close($ch);

// Return the response
echo $response;
