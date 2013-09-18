<?php
namespace OmekaTest\Api;

class AbstractAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $adapter = $this->getMockForAbstractClass('Omeka\Api\Adapter\AbstractAdapter');
        
    }
}
