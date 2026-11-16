<?php
namespace Omeka\Stdlib;

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
        // reads nothing from it.
        $members = $this->members('rdfa-mention.ttl', 'http://example.org/mention#', ['format' => 'guess']);
        $this->assertSame(['classes' => [], 'properties' => []], $members);
        // Read as Turtle, it has its terms.
        $members = $this->members('rdfa-mention.ttl', 'http://example.org/mention#', ['format' => 'turtle']);
        $this->assertSame(['Thing'], array_keys($members['classes']));
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
        }
    }
}
