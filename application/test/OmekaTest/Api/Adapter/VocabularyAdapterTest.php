<?php
namespace OmekaTest\Api\Adapter;

use Omeka\Api\Exception\ValidationException;
use Omeka\Entity\User;
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

    public function testTermsNotInAListAreReportedUnderTheirFields()
    {
        $this->assertSame(['o:class', 'o:property'], $this->errorFields([
            'o:class' => 'Thing',
            'o:property' => 'name',
        ]));
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
