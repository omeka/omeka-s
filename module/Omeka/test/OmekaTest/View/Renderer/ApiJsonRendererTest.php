<?php
namespace OmekaTest\View\Renderer;

use Omeka\View\Renderer\ApiJsonRenderer;
use Zend\Json\Json;

class ApiJsonRendererTest extends \PHPUnit_Framework_TestCase
{
    public function testRendererUsesApiResponse()
    {
        $testValue = array('test' => 'foo');
        $response = $this->getMock('Omeka\Api\Response');
        $response->expects($this->once())
                 ->method('getContent')
                 ->will($this->returnValue($testValue));

        $model = $this->getMock('Omeka\View\Model\ApiJsonModel');
        $model->expects($this->once())
              ->method('getApiResponse')
              ->will($this->returnValue($response));

        $renderer = new ApiJsonRenderer;
        $this->assertEquals(Json::encode($testValue), $renderer->render($model));
    }

    public function testRendererPassesOnNullResponse()
    {
        $response = $this->getMock('Omeka\Api\Response');
        $response->expects($this->once())
                 ->method('getContent')
                 ->will($this->returnValue(null));

        $model = $this->getMock('Omeka\View\Model\ApiJsonModel');
        $model->expects($this->once())
              ->method('getApiResponse')
              ->will($this->returnValue($response));

        $renderer = new ApiJsonRenderer;
        $this->assertEquals(null, $renderer->render($model));
    }
}
