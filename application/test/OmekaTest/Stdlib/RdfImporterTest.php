<?php
namespace Omeka\Stdlib;

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
}
