<?php
namespace Omeka\Stdlib;

use EasyRdf\Graph;
use EasyRdf\Http;
use EasyRdf\Http\Client as HttpClient;
use EasyRdf\Http\Response;
use Omeka\Api\Exception\ValidationException;
use Omeka\Test\DbTestCase;

class RdfImporterTest extends DbTestCase
{
    /**
     * @var RdfImporter
     */
    protected $rdfImporter;

    public function setUp(): void
    {
        parent::setUp();

        $services = self::getApplication()->getServiceManager();
        $apiManager = $services->get('Omeka\ApiManager');
        $entityManager = $services->get('Omeka\EntityManager');
        $this->rdfImporter = new RdfImporter($apiManager, $entityManager);
    }

    public function tearDown(): void
    {
        if ($this->httpClient) {
            Http::setDefaultHttpClient($this->httpClient);
        }
        parent::tearDown();
    }

    /**
     * EasyRdf's HTTP client, while a test answers its requests.
     *
     * @var HttpClient|null
     */
    protected $httpClient;

    /**
     * Answer EasyRdf's requests with a response, in place of a server.
     *
     * @param Response $response
     * @return HttpClient The client answering, which counts the requests in
     *   $requests
     */
    protected function answerRequests(Response $response)
    {
        $client = new class extends HttpClient {
            public $response;
            public $requests = 0;

            public function request($method = null)
            {
                $this->requests++;
                return $this->response;
            }
        };
        $client->response = $response;
        $this->httpClient = Http::getDefaultHttpClient();
        Http::setDefaultHttpClient($client);
        return $client;
    }

    public function testGetMembers()
    {
        $strategy = 'file';
        $namespaceUri = 'http://localhost.test';
        $options = [
            'format' => 'jsonld',
            'file' => __DIR__ . '/_files/test.json',
        ];
        // Issue in library ml/jsonld for php 7.2
        // @see https://github.com/lanthaler/JsonLD/pull/92
        $members = $this->rdfImporter->getMembers($strategy, $namespaceUri, $options);
        $this->assertNotNull($members);
    }

    public function testGetMembersReadsTypedLiteralsAsText()
    {
        $members = $this->rdfImporter->getMembers('file', 'http://example.org/typed#', [
            'format' => 'turtle',
            'file' => __DIR__ . '/_files/typed-literals.ttl',
        ]);
        $this->assertSame(['Number' => ['label' => '1', 'comment' => '2020-01-01']], $members['classes']);
        $this->assertSame(['flag' => ['label' => 'false', 'comment' => 'true']], $members['properties']);
    }

    /**
     * The terms of one of the tests' files.
     *
     * @param string $file A file in _files
     * @param string $namespaceUri
     * @param array $options The importer's reading options
     * @return array
     */
    protected function members($file, $namespaceUri, array $options = [])
    {
        return $this->rdfImporter->getMembers('file', $namespaceUri, ['file' => __DIR__ . '/_files/' . $file] + $options);
    }

    public function testReadingInALanguageWithEmptyValues()
    {
        $members = $this->members('multilingual.ttl', 'http://example.org/multi#', ['format' => 'turtle', 'lang' => 'fr']);
        // Empty French values are placeholders, so the English ones are read.
        $this->assertSame(['label' => 'Work', 'comment' => 'A creative work.'], $members['classes']['CreativeWork']);
        $this->assertSame(['label' => 'Titre', 'comment' => "Le titre de l'oeuvre."], $members['properties']['title']);
        // With only placeholders, blank or empty, a term has its local name
        // for a label and no comment.
        $this->assertSame(['label' => 'subtitle', 'comment' => null], $members['properties']['subtitle']);
    }

    public function testReadingAFileThatMentionsRdfa()
    {
        // Guessing its format, EasyRdf takes the Turtle file for RDFa and
        // reads nothing from it, so the formats are tried in turn.
        $members = $this->members('rdfa-mention.ttl', 'http://example.org/mention#', ['format' => 'guess']);
        $this->assertSame(['Thing'], array_keys($members['classes']));
        // Read as Turtle, it has its terms.
        $members = $this->members('rdfa-mention.ttl', 'http://example.org/mention#', ['format' => 'turtle']);
        $this->assertSame(['Thing'], array_keys($members['classes']));
    }

    public function testReadingAUrlWhoseFormatIsMisread()
    {
        // A server says the Turtle file is HTML, so EasyRdf reads it as RDFa,
        // and reads nothing. Asked again for RDF/XML and then Turtle, the
        // server sends the same file, which reads as Turtle.
        $client = $this->answerRequests(new Response(200, ['Content-Type' => 'text/html'], file_get_contents(__DIR__ . '/_files/rdfa-mention.ttl')));
        $members = $this->rdfImporter->getMembers('url', 'http://example.org/mention#', ['url' => 'http://example.org/mention.ttl']);
        $this->assertSame(['Thing'], array_keys($members['classes']));
        $this->assertSame(3, $client->requests);
    }

    public function testReadingAUrlTheServerRefuses()
    {
        // Another format wouldn't change the server's answer, so it's asked
        // once.
        $client = $this->answerRequests(new Response(404, [], 'Not found'));
        try {
            $this->rdfImporter->getMembers('url', 'http://example.org/mention#', ['url' => 'http://example.org/missing.ttl']);
            $this->fail('The URL was read');
        } catch (ValidationException $e) {
            $this->assertSame('Could not load vocabulary from URL.', $e->getMessage());
        }
        $this->assertSame(1, $client->requests);
    }

    public function testReadingInAFormatTheFileIsNotIn()
    {
        // A format the user chose isn't second-guessed.
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Could not parse vocabulary file.');
        $this->members('rdfa-mention.ttl', 'http://example.org/mention#', ['format' => 'rdfxml']);
    }

    public function testReadingAnEmptyFile()
    {
        // Some formats read an empty file as empty RDF, but none reads
        // anything from it, so it's an error, as when guessing alone.
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Could not parse vocabulary file.');
        $this->members('empty.ttl', 'http://example.org/empty#', ['format' => 'guess']);
    }

    public function testImportingWithAnotherFormOfTheNamespace()
    {
        try {
            $this->rdfImporter->import('file', [
                'o:namespace_uri' => 'http://example.org/multi',
                'o:prefix' => 'multi',
                'o:label' => 'Multi',
            ], [
                'file' => __DIR__ . '/_files/multilingual.ttl',
                'format' => 'turtle',
            ]);
            $this->fail('The vocabulary was imported');
        } catch (ValidationException $e) {
            $this->assertSame('No classes or properties found. Are you sure you used the correct namespace URI?', $e->getMessage());
            // And the namespace the file's terms use.
            $errors = $e->getErrorStore()->getErrors();
            $this->assertSame(['o:namespace_uri'], array_keys($errors));
            $this->assertSame(
                ['The file\'s classes and properties use the namespace URI "http://example.org/multi#".'],
                array_map('strval', $errors['o:namespace_uri'])
            );
        }
    }

    public function namespaceSuggestions()
    {
        $prefixes = <<<'TTL'
            @prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
            @prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
            @prefix owl: <http://www.w3.org/2002/07/owl#> .
            @prefix vann: <http://purl.org/vocab/vann/> .

            TTL;
        // Two terms elsewhere, so the namespace most terms use isn't the one
        // expected, where a declared or ontology namespace is.
        $elsewhere = '<http://example.org/elsewhere#a> a rdf:Property . <http://example.org/elsewhere#b> a rdf:Property .';
        return [
            'another form of the given one' => [
                $prefixes . '<http://example.org/ns#Thing> a rdfs:Class .',
                'https://example.org/ns',
                'http://example.org/ns#',
            ],
            'the one the file declares' => [
                $prefixes . '<http://example.org/doc> vann:preferredNamespaceUri "http://example.org/declared/" .
                <http://example.org/declared/Thing> a rdfs:Class . ' . $elsewhere,
                'http://example.org/given#',
                'http://example.org/declared/',
            ],
            'a form of the ontology\'s URI' => [
                $prefixes . '<http://example.org/ontology> a owl:Ontology .
                <http://example.org/ontology#Thing> a owl:Class . ' . $elsewhere,
                'http://example.org/given#',
                'http://example.org/ontology#',
            ],
            'the one most terms use' => [
                $prefixes . '<http://example.org/few#a> a rdf:Property . ' . $elsewhere,
                'http://example.org/given#',
                'http://example.org/elsewhere#',
            ],
            'none, without terms' => [
                $prefixes . '<http://example.org/doc> rdfs:label "No terms" .',
                'http://example.org/given#',
                null,
            ],
        ];
    }

    /**
     * @dataProvider namespaceSuggestions
     */
    public function testSuggestNamespaceUri($turtle, $namespaceUri, $suggestion)
    {
        $graph = new Graph;
        $graph->parse($turtle, 'turtle');
        $this->assertSame($suggestion, $this->rdfImporter->suggestNamespaceUri($graph, $namespaceUri));
    }
}
