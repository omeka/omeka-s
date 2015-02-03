<?php
namespace OmekaTest\Job;

use Omeka\Test\TestCase;

class AbstractJobTest extends TestCase
{
    protected $abstractJob;

    public function setUp()
    {
        $this->abstractJob = $this->getMockForAbstractClass('Omeka\Job\AbstractJob');
    }

    public function testSetServiceLocator()
    {
        $serviceLocator = $this->getServiceManager();
        $this->abstractJob->setServiceLocator($serviceLocator);
        $this->assertSame($serviceLocator, $this->abstractJob->getServiceLocator());
    }

    public function testSetArgs()
    {
        $args = 'test-args';
        $this->abstractJob->setArgs($args);
        $this->assertEquals($args, $this->abstractJob->getArgs());
    }
}
