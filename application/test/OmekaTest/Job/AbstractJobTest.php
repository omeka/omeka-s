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

    public function testGetArg()
    {
        $this->assertNull($this->abstractJob->getArg('foobar'));

        $args = array('foo' => 'bar', 'baz' => 'bat');
        $this->abstractJob->setArgs($args);
        $this->assertEquals($args['foo'], $this->abstractJob->getArg('foo'));
        $this->assertEquals($args['baz'], $this->abstractJob->getArg('baz'));
        $this->assertNull($this->abstractJob->getArg('foobar'));
    }
}
