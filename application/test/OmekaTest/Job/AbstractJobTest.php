<?php
namespace OmekaTest\Job;

use Omeka\Test\TestCase;

class AbstractJobTest extends TestCase
{
    public function testGetArg()
    {
        $args = array('foo' => 'bar', 'baz' => 'bat');

        $job = $this->getMock('Omeka\Model\Entity\Job');
        $job->expects($this->any())
            ->method('getArgs')
            ->will($this->returnValue($args));
        $serviceLocator = $this->getServiceManager();

        $this->abstractJob = $this->getMockForAbstractClass(
            'Omeka\Job\AbstractJob',
            array($job, $serviceLocator)
        );

        $this->assertEquals($args['foo'], $this->abstractJob->getArg('foo'));
        $this->assertEquals($args['baz'], $this->abstractJob->getArg('baz'));
        $this->assertNull($this->abstractJob->getArg('foobar'));
    }
}
