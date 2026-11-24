<?php
namespace OmekaTest\Api\Adapter;

use Omeka\Api\Exception\ValidationException;
use Omeka\Entity\User;
use Omeka\Test\DbTestCase;

/**
 * How the API checks a property's or a resource class's local name and
 * label.
 */
class TermValidationTest extends DbTestCase
{
    /**
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected $services;

    /**
     * @var int
     */
    protected $vocabularyId;

    public function setUp(): void
    {
        parent::setUp();
        $this->services = self::getApplication()->getServiceManager();
        $admin = $this->services->get('Omeka\EntityManager')->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);
        $this->services->get('Omeka\AuthenticationService')->getStorage()->write($admin);
        $this->vocabularyId = $this->api()->create('vocabularies', [
            'o:namespace_uri' => 'http://example.org/terms#',
            'o:prefix' => 'terms',
            'o:label' => 'Terms',
        ])->getContent()->id();
    }

    public function tearDown(): void
    {
        $this->services->get('Omeka\AuthenticationService')->clearIdentity();
        parent::tearDown();
    }

    protected function api()
    {
        return $this->services->get('Omeka\ApiManager');
    }

    public function resources()
    {
        return [
            'properties' => ['properties'],
            'resource classes' => ['resource_classes'],
        ];
    }

    /**
     * Create a term of the tests' vocabulary.
     *
     * @return \Omeka\Api\Representation\AbstractEntityRepresentation
     */
    protected function create($resource, $localName, $label)
    {
        return $this->api()->create($resource, [
            'o:local_name' => $localName,
            'o:label' => $label,
            'o:vocabulary' => ['o:id' => $this->vocabularyId],
        ])->getContent();
    }

    /**
     * The fields the API names in refusing to create a term.
     *
     * @return string[]
     */
    protected function refusedFields($resource, $localName, $label)
    {
        try {
            $this->create($resource, $localName, $label);
        } catch (ValidationException $e) {
            return array_keys($e->getErrorStore()->getErrors());
        }
        $this->fail('The term was created');
    }

    /**
     * @dataProvider resources
     */
    public function testZeroIsALocalNameAndALabel($resource)
    {
        $term = $this->create($resource, '0', '0');
        $this->assertSame('0', $term->localName());
        $this->assertSame('0', $term->label());
    }

    /**
     * @dataProvider resources
     */
    public function testLocalNameAndLabelAreTrimmed($resource)
    {
        $term = $this->create($resource, " spaced\n", '  Spaced ');
        $this->assertSame('spaced', $term->localName());
        $this->assertSame('Spaced', $term->label());
    }

    /**
     * @dataProvider resources
     */
    public function testEmptyLocalNameAndLabelAreRefused($resource)
    {
        $this->assertSame(['o:local_name', 'o:label'], $this->refusedFields($resource, '  ', ''));
    }

    /**
     * @dataProvider resources
     */
    public function testLengthsOmekaCanStore($resource)
    {
        $term = $this->create($resource, str_repeat('a', 190), str_repeat('b', 255));
        $this->assertSame(190, mb_strlen($term->localName()));
        $this->assertSame(['o:local_name'], $this->refusedFields($resource, str_repeat('c', 191), 'Label'));
        $this->assertSame(['o:label'], $this->refusedFields($resource, 'name', str_repeat('d', 256)));
    }
}
