<?php
namespace OmekaTest\View\Strategy;

use Omeka\Api\Response as ApiResponse;
use Omeka\View\Renderer\ApiJsonRenderer;
use Omeka\View\Strategy\ApiJsonStrategy;
use Zend\Http\Response as HttpResponse;
use Zend\View\ViewEvent;

class ApiJsonStrategyTest extends \PHPUnit_Framework_TestCase
{
    public $renderer;
    public $strategy;
    public $event;

    public function setUp()
    {
        $this->renderer = $this->getMock('Omeka\View\Renderer\ApiJsonRenderer');

        $this->strategy = new ApiJsonStrategy($this->renderer);

        $this->event = new ViewEvent;
        $httpResponse = new HttpResponse;
        $this->event->setResponse($httpResponse);
        $this->event->setResult('{}');
    }

    public function testStrategyPicksRendererForApiJsonModel()
    {
        $model = $this->getMock('Omeka\View\Model\ApiJsonModel');

        $this->event->setModel($model);

        $this->assertSame($this->renderer, $this->strategy->selectRenderer($this->event));
    }

    public function statusProvider()
    {
        return array(
            array(ApiResponse::SUCCESS, 200),
            array(ApiResponse::ERROR_VALIDATION, 422),
            array(ApiResponse::ERROR_NOT_FOUND, 404),
            array(ApiResponse::ERROR_INTERNAL, 500),
            array('foo', 500)
        );
    }

    /**
     * @dataProvider statusProvider
     */
    public function testStrategySetsStatus($apiStatus, $httpStatus)
    {
        $apiResponse = $this->getMock('Omeka\Api\Response');
        $apiResponse->expects($this->once())
                    ->method('getStatus')
                    ->will($this->returnValue($apiStatus));

        $model = $this->getMock('Omeka\View\Model\ApiJsonModel');
        $model->expects($this->once())
              ->method('getApiResponse')
              ->will($this->returnValue($apiResponse));

        $this->event->setModel($model);
        $this->event->setRenderer($this->renderer);
        $this->strategy->injectResponse($this->event);
        $this->assertEquals($httpStatus, $this->event->getResponse()->getStatusCode());
    }

    public function testStrategySetsContentType()
    {
        $apiResponse = $this->getMock('Omeka\Api\Response');
        $apiResponse->expects($this->once())
                    ->method('getStatus')
                    ->will($this->returnValue(200));

        $model = $this->getMock('Omeka\View\Model\ApiJsonModel');
        $model->expects($this->once())
              ->method('getApiResponse')
              ->will($this->returnValue($apiResponse));

        $this->event->setModel($model);
        $this->event->setRenderer($this->renderer);
        $this->strategy->injectResponse($this->event);

        $headers = $this->event->getResponse()->getHeaders();
        $expectedContentType = 'application/json; charset=utf-8';
        $this->assertEquals($expectedContentType, $headers->get('Content-Type')->value);
    }
}
