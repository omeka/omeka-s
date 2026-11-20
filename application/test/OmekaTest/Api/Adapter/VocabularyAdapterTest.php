<?php
namespace OmekaTest\Api\Adapter;

use Omeka\Api\Exception\ValidationException;
use Omeka\Entity\User;
use Omeka\Entity\Vocabulary;
use Omeka\Test\DbTestCase;

/**
 * The API reports errors in a vocabulary under the fields at fault.
 */
class VocabularyAdapterTest extends DbTestCase
{
    /**
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected $services;

    public function setUp(): void
    {
        parent::setUp();
        $this->services = self::getApplication()->getServiceManager();
        $admin = $this->services->get('Omeka\EntityManager')->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);
        $this->services->get('Omeka\AuthenticationService')->getStorage()->write($admin);
    }

    public function tearDown(): void
    {
        $this->services->get('Omeka\AuthenticationService')->clearIdentity();
        parent::tearDown();
    }

    /**
     * The fields the API names in refusing to create a vocabulary.
     *
     * @param array $data
     * @return string[]
     */
    protected function errorFields(array $data)
    {
        try {
            $this->services->get('Omeka\ApiManager')->create('vocabularies', $data + [
                'o:namespace_uri' => 'http://example.org/errors#',
                'o:prefix' => 'errors',
                'o:label' => 'Errors',
            ]);
        } catch (ValidationException $e) {
            return array_keys($e->getErrorStore()->getErrors());
        }
        $this->fail('The vocabulary was created');
    }

    /**
     * Create a vocabulary.
     *
     * @param array $data
     * @return \Omeka\Api\Representation\VocabularyRepresentation
     */
    protected function create(array $data = [])
    {
        return $this->services->get('Omeka\ApiManager')->create('vocabularies', $data + [
            'o:namespace_uri' => 'http://example.org/options#',
            'o:prefix' => 'options',
            'o:label' => 'Options',
        ])->getContent();
    }

    public function testImportOptionsAreTheImportersReadingOptions()
    {
        $vocabulary = $this->create(['o:import_options' => [
            'lang' => 'fr',
            'file' => '/tmp/vocabulary.ttl',
            'comment_property' => 'skos:definition|rdfs:comment',
            'label_property' => null,
            'format' => 'turtle',
        ]]);
        // The reading options only, in the order given.
        $this->assertSame([
            'lang' => 'fr',
            'comment_property' => 'skos:definition|rdfs:comment',
            'label_property' => null,
            'format' => 'turtle',
        ], $vocabulary->importOptions());
        // They're in the JSON-LD, as the API takes them.
        $this->assertSame($vocabulary->importOptions(), $vocabulary->jsonSerialize()['o:import_options']);

        // An update that doesn't mention them keeps them.
        $vocabulary = $this->services->get('Omeka\ApiManager')->update('vocabularies', $vocabulary->id(), [
            'o:label' => 'Renamed',
        ], [], ['isPartial' => true])->getContent();
        $this->assertSame('fr', $vocabulary->importOptions()['lang']);
    }

    public function testSortByOwnerName()
    {
        // Omeka's own have no owner, so the admin's comes last going up
        // and first going down.
        $vocabulary = $this->create();
        $api = $this->services->get('Omeka\ApiManager');
        $ids = function ($order) use ($api) {
            return $api->search('vocabularies', ['sort_by' => 'owner_name', 'sort_order' => $order], ['returnScalar' => 'id'])->getContent();
        };
        $ascending = $ids('asc');
        $descending = $ids('desc');
        $this->assertSame($vocabulary->id(), (int) end($ascending));
        $this->assertSame($vocabulary->id(), (int) reset($descending));
    }

    public function testVocabularyWithoutImportOptions()
    {
        $this->assertNull($this->create()->importOptions());
        $this->assertNull($this->create([
            'o:namespace_uri' => 'http://example.org/none#',
            'o:prefix' => 'none',
            'o:import_options' => ['file' => '/tmp/vocabulary.ttl'],
        ])->importOptions());
    }

    public function testImportOptionsNotInAListAreReportedUnderTheirField()
    {
        $this->assertSame(['o:import_options'], $this->errorFields(['o:import_options' => 'fr']));
    }

    public function testTermsNotInAListAreReportedUnderTheirFields()
    {
        $this->assertSame(['o:class', 'o:property'], $this->errorFields([
            'o:class' => 'Thing',
            'o:property' => 'name',
        ]));
    }

    /**
     * Create a vocabulary as Omeka did before it refused another form of a
     * vocabulary's namespace URI, as an installation may still have one.
     *
     * @return int Its id
     */
    protected function createVocabularyAsBefore($namespaceUri, $prefix, $label)
    {
        $entityManager = $this->services->get('Omeka\EntityManager');
        $vocabulary = new Vocabulary;
        $vocabulary->setNamespaceUri($namespaceUri);
        $vocabulary->setPrefix($prefix);
        $vocabulary->setLabel($label);
        $entityManager->persist($vocabulary);
        $entityManager->flush();
        return $vocabulary->getId();
    }

    public function testAnotherFormOfANamespaceUriIsReportedWithOtherErrors()
    {
        $this->create();
        // Another form of its namespace URI, and its prefix.
        $this->assertSame(['o:namespace_uri', 'o:prefix'], $this->errorFields([
            'o:namespace_uri' => 'https://example.org/options/',
            'o:prefix' => 'options',
        ]));
    }

    public function testFullUpdateOfAVocabularyHeldInTwoForms()
    {
        // As an installation may have from before: one vocabulary in http,
        // and another in https.
        $vocabulary = $this->create();
        $this->createVocabularyAsBefore('https://example.org/options#', 'optionshttps', 'Options in https');
        $updated = $this->services->get('Omeka\ApiManager')->update('vocabularies', $vocabulary->id(), [
            'o:namespace_uri' => 'http://example.org/options#',
            'o:prefix' => 'options',
            'o:label' => 'Renamed',
        ])->getContent();
        $this->assertSame('Renamed', $updated->label());
    }

    public function testFindByNamespaceUri()
    {
        // As an installation may have from before: one vocabulary in http,
        // and a newer one in https.
        $http = $this->create();
        $httpsId = $this->createVocabularyAsBefore('https://example.org/options/', 'optionshttps', 'Options in https');
        $adapter = $this->services->get('Omeka\ApiAdapterManager')->get('vocabularies');
        // An exact match first, whatever its capitals, as the database
        // matches.
        $this->assertSame($httpsId, $adapter->findByNamespaceUri('HTTPS://Example.org/options/')->getId());
        // Else the oldest.
        $this->assertSame($http->id(), $adapter->findByNamespaceUri('https://example.org/options#')->getId());
        // Leaving one out.
        $this->assertSame($httpsId, $adapter->findByNamespaceUri('https://example.org/options#', $http->id())->getId());
        $this->assertNull($adapter->findByNamespaceUri('http://example.org/elsewhere#'));
    }

    public function testRepeatedPropertyIsReportedUnderProperties()
    {
        $this->assertSame(['o:property'], $this->errorFields([
            'o:property' => [
                ['o:local_name' => 'name', 'o:label' => 'Name'],
                ['o:local_name' => 'name', 'o:label' => 'Name again'],
            ],
        ]));
    }
}
