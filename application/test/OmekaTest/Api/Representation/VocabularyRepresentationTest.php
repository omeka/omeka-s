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
            'o:property' => [['o:local_name' => 'name', 'o:label' => 'Name'], ['o:local_name' => 'title', 'o:label' => 'Title']],
        ])->getContent();
        $this->assertSame([0, 0, 0], $this->counts($vocabulary->id()));

        $classId = $vocabulary->resourceClasses()[0]->id();
        [$propertyId, $otherPropertyId] = array_map(function ($property) {
            return $property->id();
        }, $vocabulary->properties());
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
            'o:resource_template_property' => [['o:property' => ['o:id' => $propertyId]], ['o:property' => ['o:id' => $otherPropertyId]]],
        ]);
        // Two values, one template (using two of its properties), and an
        // item and an item set.
        $this->assertSame([2, 1, 2], $this->counts($vocabulary->id()));
    }

    /**
     * The numbers of values and resource templates using a vocabulary's
     * properties, and of resources using its classes.
     *
     * @param int $vocabularyId
     * @return int[]
     */
    protected function counts($vocabularyId)
    {
        $vocabulary = $this->api->read('vocabularies', $vocabularyId)->getContent();
        return [$vocabulary->valueCount(), $vocabulary->resourceTemplateCount(), $vocabulary->resourceWithClassCount()];
    }
}
