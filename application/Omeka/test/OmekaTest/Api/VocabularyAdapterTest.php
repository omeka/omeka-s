<?php
namespace OmekaTest\Api;

use Omeka\Api\Adapter\Entity\VocabularyAdapter;
use Omeka\Model\Entity\User;
use Omeka\Model\Entity\Vocabulary;
use Omeka\Test\MockBuilder;

class VocabularyAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected $data = array(
        'owner' => array('id' => 1),
        'namespace_uri' => 'http://example.com/',
        'label' => 'Label',
        'comment' => 'Comment',
    );

    public function setUp()
    {
        $this->adapter = new VocabularyAdapter;
    }

    public function testGetEntityClass()
    {
        $this->assertEquals(
            'Omeka\Model\Entity\Vocabulary',
            $this->adapter->getEntityClass()
        );
    }

    public function testHydrate()
    {
        $builder = new MockBuilder;
        $entityRepository = $builder->getEntityRepository();
        $entityManager = $builder->getEntityManager();
        $serviceManager = $builder->getServiceManager(array('EntityManager' => $entityManager));

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Omeka\Model\Entity\User'))
            ->will($this->returnValue($entityRepository));
        $entityRepository->expects($this->once())
            ->method('find')
            ->with($this->equalTo($this->data['owner']['id']))
            ->will($this->returnValue(new User));

        $this->adapter->setServiceLocator($serviceManager);
        $entity = new Vocabulary;
        $this->adapter->hydrate($this->data, $entity);

        $this->assertNull($entity->getOwner()->getId());
        $this->assertInstanceOf('Omeka\Model\Entity\User', $entity->getOwner());
        $this->assertEquals($this->data['namespace_uri'], $entity->getNamespaceUri());
        $this->assertEquals($this->data['label'], $entity->getLabel());
        $this->assertEquals($this->data['comment'], $entity->getComment());
    }

    public function testExtract()
    {
        $entity = new Vocabulary;
        $entity->setOwner(new User);
        $entity->setNamespaceUri($this->data['namespace_uri']);
        $entity->setLabel($this->data['label']);
        $entity->setComment($this->data['comment']);
        $data = $this->adapter->extract($entity);
        $this->assertNull($data['id']);
        $this->assertInternalType('array', $data['owner']);
        $this->assertEquals($this->data['namespace_uri'], $data['namespace_uri']);
        $this->assertEquals($this->data['label'], $data['label']);
        $this->assertEquals($this->data['comment'], $data['comment']);
    }
}
