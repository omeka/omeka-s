<?php
namespace OmekaTest\Model;

//use PHPUnit_Framework_TestCase;
use OmekaTest\Bootstrap;
use Omeka\Test\ModelTest;
use Omeka\Model\ResourceClass;
use Omeka\Model\Resource;

class ResourceClassTest extends ModelTest
{
    protected $className = 'Omeka\\Model\\ResourceClass';

    protected function setUpNewEntity($entity)
    {
        $entity->setLabel("new label");
        $entity->setResourceType(RESOURCE::TYPE_ITEM);
        $entity->setIsDefault(false);
    }
    
    protected function updateNewEntity($entity) 
    {
        $entity->setLabel('updated label');
        $this->em->flush($entity);
        $updated = $this->em->getRepository($this->className)->find(1);
        $this->assertEquals($updated->getLabel(), 'updated label');
    }
}
