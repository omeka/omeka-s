<?php
namespace OmekaTest\Api\Representation;

use Omeka\Entity\User;
use Omeka\Test\DbTestCase;

/**
 * What deleting a vocabulary would take with it.
 */
class VocabularyRepresentationTest extends DbTestCase
{
    /**
     * @var \Omeka\Api\Manager
     */
    protected $api;

    public function setUp(): void
    {
        parent::setUp();
        $services = self::getApplication()->getServiceManager();
        $admin = $services->get('Omeka\EntityManager')->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);
        $services->get('Omeka\AuthenticationService')->getStorage()->write($admin);
        $this->api = $services->get('Omeka\ApiManager');
    }

    public function tearDown(): void
    {
        self::getApplication()->getServiceManager()->get('Omeka\AuthenticationService')->clearIdentity();
        parent::tearDown();
    }

    public function testCountsOfWhatDeletingTakes()
    {
        $vocabulary = $this->api->create('vocabularies', [
            'o:namespace_uri' => 'http://example.org/used#',
            'o:prefix' => 'used',
            'o:label' => 'Used',
            'o:class' => [['o:local_name' => 'Thing', 'o:label' => 'Thing']],
            'o:property' => [['o:local_name' => 'name', 'o:label' => 'Name']],
        ])->getContent();
        $this->assertSame([0, 0, 0], $this->counts($vocabulary->id()));

        $classId = $vocabulary->resourceClasses()[0]->id();
        $propertyId = $vocabulary->properties()[0]->id();
        $value = ['type' => 'literal', 'property_id' => $propertyId, '@value' => 'A name'];
        $this->api->create('items', [
            'o:resource_class' => ['o:id' => $classId],
            'used:name' => [$value, ['@value' => 'Another name'] + $value],
        ]);
        $this->api->create('item_sets', [
            'o:resource_class' => ['o:id' => $classId],
        ]);
        $this->api->create('resource_templates', [
            'o:label' => 'Used',
            'o:resource_template_property' => [['o:property' => ['o:id' => $propertyId]]],
        ]);
        // Two values, one template property, and an item and an item set.
        $this->assertSame([2, 1, 2], $this->counts($vocabulary->id()));
    }

    /**
     * A vocabulary's values, resource template properties, and resources
     * with its classes.
     *
     * @param int $vocabularyId
     * @return int[]
     */
    protected function counts($vocabularyId)
    {
        $vocabulary = $this->api->read('vocabularies', $vocabularyId)->getContent();
        return [$vocabulary->valueCount(), $vocabulary->resourceTemplatePropertyCount(), $vocabulary->resourceCount()];
    }
}
