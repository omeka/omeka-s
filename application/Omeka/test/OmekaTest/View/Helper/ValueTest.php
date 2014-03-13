<?php
namespace OmekaTest\View\Helper;

use Omeka\View\Helper\Value;

class ValueTest extends \PHPUnit_Framework_TestCase
{
    public function testSetsAndGetsResponseFilter()
    {
        $mockResponseFilter = $this->getMock('Omeka\Api\ResponseFilter');
        $valueHelper = new Value;
        $valueHelper->setResponseFilter($mockResponseFilter);
        $responseFilter = $valueHelper->getResponseFilter();
        $this->assertSame($responseFilter, $mockResponseFilter);
    }

    public function testDefaultState()
    {
        $resourceId   = 1;
        $namespaceUri = 'namespace_uri';
        $localName    = 'local_name';
        $options      = array();
        $expectedReturnValue = 'expected_return_value';

        $mockResponseFilter = $this->getMock('Omeka\Api\ResponseFilter');
        $mockResponseFilter->expects($this->at(0))
            ->method('get')
            ->with(
                $this->isInstanceOf('Omeka\Api\Response'),
                $this->equalTo('id'),
                $this->equalTo(array('one' => true))
            );
        $mockResponseFilter->expects($this->at(1))
            ->method('get')
            ->with(
                $this->isInstanceOf('Omeka\Api\Response'),
                $this->equalTo('value'),
                $this->equalTo(array(
                    'one'        => true,
                    'delimiter'  => false,
                    'default'    => null,
                    'default_if' => array(''),
                    'callbacks'  => array('trim', function(){}),
                ))
            )->will($this->returnValue($expectedReturnValue));

        $mockResponse = $this->getMock('Omeka\Api\Response');
        $mockResponse->expects($this->at(0))
            ->method('isError')
            ->will($this->returnValue(false));
        $mockResponse->expects($this->at(1))
            ->method('getTotalResults')
            ->will($this->returnValue(1));
        $mockResponse->expects($this->at(2))
            ->method('isError')
            ->will($this->returnValue(false));
        $mockResponse->expects($this->at(3))
            ->method('getTotalResults')
            ->will($this->returnValue(1));

        $mockView = $this->getMock('Zend\View\Renderer\PhpRenderer', array('api', 'search'));
        $mockView->expects($this->at(0))
            ->method('api')
            ->will($this->returnSelf());
        $mockView->expects($this->at(1))
            ->method('search')
            ->with($this->equalTo('properties'), $this->equalTo(array(
                'vocabulary' => array('namespace_uri' => $namespaceUri),
                'local_name' => $localName,
            )))
            ->will($this->returnValue($mockResponse));
        $mockView->expects($this->at(2))
            ->method('api')
            ->will($this->returnSelf());
        $mockView->expects($this->at(3))
            ->method('search')
            ->with($this->equalTo('values'), $this->equalTo(array(
                'resource' => array('id' => $resourceId),
                'property' => array('id' => null),
                'type' => 'literal',
            )))
            ->will($this->returnValue($mockResponse));

        $valueHelper = new Value;
        $valueHelper->setView($mockView);
        $valueHelper->setResponseFilter($mockResponseFilter);

        $value = $valueHelper($resourceId, $namespaceUri, $localName, $options);
        $this->assertEquals($value, $expectedReturnValue);
    }

    public function testPropertyRequestError()
    {
        $this->setExpectedException('Zend\View\Exception\RuntimeException');

        $resourceId   = 1;
        $namespaceUri = 'namespace_uri';
        $localName    = 'local_name';
        $options      = array();
        $expectedReturnValue = 'expected_return_value';

        $mockResponseFilter = $this->getMock('Omeka\Api\ResponseFilter');

        $mockResponse = $this->getMock('Omeka\Api\Response');
        $mockResponse->expects($this->at(0))
            ->method('isError')
            ->will($this->returnValue(true));

        $mockView = $this->getMock('Zend\View\Renderer\PhpRenderer', array('api', 'search'));
        $mockView->expects($this->at(0))
            ->method('api')
            ->will($this->returnSelf());
        $mockView->expects($this->at(1))
            ->method('search')
            ->with($this->equalTo('properties'), $this->equalTo(array(
                'vocabulary' => array('namespace_uri' => $namespaceUri),
                'local_name' => $localName,
            )))
            ->will($this->returnValue($mockResponse));

        $valueHelper = new Value;
        $valueHelper->setView($mockView);
        $valueHelper->setResponseFilter($mockResponseFilter);

        $value = $valueHelper($resourceId, $namespaceUri, $localName, $options);
    }

    public function testPropertyNotFoundError()
    {
        $this->setExpectedException('Zend\View\Exception\InvalidArgumentException');

        $resourceId   = 1;
        $namespaceUri = 'namespace_uri';
        $localName    = 'local_name';
        $options      = array();
        $expectedReturnValue = 'expected_return_value';

        $mockResponseFilter = $this->getMock('Omeka\Api\ResponseFilter');

        $mockResponse = $this->getMock('Omeka\Api\Response');
        $mockResponse->expects($this->at(0))
            ->method('isError')
            ->will($this->returnValue(false));
        $mockResponse->expects($this->at(1))
            ->method('getTotalResults')
            ->will($this->returnValue(0));

        $mockView = $this->getMock('Zend\View\Renderer\PhpRenderer', array('api', 'search'));
        $mockView->expects($this->at(0))
            ->method('api')
            ->will($this->returnSelf());
        $mockView->expects($this->at(1))
            ->method('search')
            ->with($this->equalTo('properties'), $this->equalTo(array(
                'vocabulary' => array('namespace_uri' => $namespaceUri),
                'local_name' => $localName,
            )))
            ->will($this->returnValue($mockResponse));

        $valueHelper = new Value;
        $valueHelper->setView($mockView);
        $valueHelper->setResponseFilter($mockResponseFilter);

        $value = $valueHelper($resourceId, $namespaceUri, $localName, $options);
    }

    public function testValueRequestError()
    {
        $this->setExpectedException('Zend\View\Exception\RuntimeException');

        $resourceId   = 1;
        $namespaceUri = 'namespace_uri';
        $localName    = 'local_name';
        $options      = array();
        $expectedReturnValue = 'expected_return_value';

        $mockResponseFilter = $this->getMock('Omeka\Api\ResponseFilter');
        $mockResponseFilter->expects($this->at(0))
            ->method('get')
            ->with(
                $this->isInstanceOf('Omeka\Api\Response'),
                $this->equalTo('id'),
                $this->equalTo(array('one' => true))
            );

        $mockResponse = $this->getMock('Omeka\Api\Response');
        $mockResponse->expects($this->at(0))
            ->method('isError')
            ->will($this->returnValue(false));
        $mockResponse->expects($this->at(1))
            ->method('getTotalResults')
            ->will($this->returnValue(1));
        $mockResponse->expects($this->at(2))
            ->method('isError')
            ->will($this->returnValue(true));

        $mockView = $this->getMock('Zend\View\Renderer\PhpRenderer', array('api', 'search'));
        $mockView->expects($this->at(0))
            ->method('api')
            ->will($this->returnSelf());
        $mockView->expects($this->at(1))
            ->method('search')
            ->with($this->equalTo('properties'), $this->equalTo(array(
                'vocabulary' => array('namespace_uri' => $namespaceUri),
                'local_name' => $localName,
            )))
            ->will($this->returnValue($mockResponse));
        $mockView->expects($this->at(2))
            ->method('api')
            ->will($this->returnSelf());
        $mockView->expects($this->at(3))
            ->method('search')
            ->with($this->equalTo('values'), $this->equalTo(array(
                'resource' => array('id' => $resourceId),
                'property' => array('id' => null),
                'type' => 'literal',
            )))
            ->will($this->returnValue($mockResponse));

        $valueHelper = new Value;
        $valueHelper->setView($mockView);
        $valueHelper->setResponseFilter($mockResponseFilter);

        $value = $valueHelper($resourceId, $namespaceUri, $localName, $options);
    }
}
