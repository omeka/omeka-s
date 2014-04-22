<?php
namespace OmekaTest\Api\Reference;

use Omeka\Api\Reference\Reference;
use Omeka\Test\TestCase;

class ReferenceTest extends TestCase
{
    protected $reference;
    protected $testData = array('foo', 'bar');
    protected $testApiUrl = 'api_url';
    protected $testWebUrl = 'web_url';

    public function setUp()
    {
        $this->reference = new Reference;
    }

    public function testSetData()
    {
        $this->reference->setData($this->testData);
        $this->assertEquals($this->testData, $this->reference->toArray());
    }

    public function testToArray()
    {
        $this->reference->setData($this->testData);
        $this->assertEquals($this->testData, $this->reference->toArray());
    }

    public function testSetAdapterRequiresValidAdapter()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        $this->reference->setAdapter(new \stdClass);
    }

    public function testSetAdapter()
    {
        $this->assertNull($this->reference->setAdapter($this->getMockAdapter()));
    }

    public function testGetApiUrl()
    {
        $this->reference->setData($this->testData);
        $this->reference->setAdapter($this->getMockAdapter());
        $this->assertEquals($this->testApiUrl, $this->reference->getApiUrl());
    }

    public function testGetWebUrl()
    {
        $this->reference->setData($this->testData);
        $this->reference->setAdapter($this->getMockAdapter());
        $this->assertEquals($this->testWebUrl, $this->reference->getWebUrl());
    }

    public function testJsonSerialize()
    {
        $this->reference->setData($this->testData);
        $this->reference->setAdapter($this->getMockAdapter());
        $this->assertEquals(
            array(
                '@id' => $this->testApiUrl,
            ),
            $this->reference->jsonSerialize()
        );
    }

    public function getMockAdapter()
    {
        $mockAdapter = $this->getMock('Omeka\Api\Adapter\AdapterInterface');
        $mockAdapter->expects($this->any())
            ->method('getApiUrl')
            ->with($this->equalTo($this->testData))
            ->will($this->returnValue($this->testApiUrl));
        $mockAdapter->expects($this->any())
            ->method('getWebUrl')
            ->with($this->equalTo($this->testData))
            ->will($this->returnValue($this->testWebUrl));
        return $mockAdapter;
    }
}
