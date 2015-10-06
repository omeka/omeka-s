<?php
namespace OmekaTest\View\Strategy;

use Omeka\Api\Exception;
use Omeka\Api\Response as ApiResponse;
use Omeka\View\Strategy\ApiJsonStrategy;
use Zend\Http\Response as HttpResponse;
use Zend\Json\Exception as JsonException;
use Zend\View\ViewEvent;
use Omeka\Test\TestCase;

class ApiJsonStrategyTest extends TestCase
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

    public function testStrategyDoesNothingForOtherModels()
    {
        $model = $this->getMock('Zend\View\Model\JsonModel');
        $model->expects($this->never())
              ->method('getOption');

        $this->event->setModel($model);

        $this->assertNull($this->strategy->selectRenderer($this->event));
        $this->strategy->injectResponse($this->event);
    }

    public function statusProvider()
    {
        return [
            [ApiResponse::SUCCESS, 'bar', null, 200],
            [ApiResponse::SUCCESS, null, null, 204],
            [ApiResponse::ERROR_VALIDATION, 'bar', null, 422],
            [ApiResponse::ERROR, 'bar', new Exception\NotFoundException, 404],
            [ApiResponse::ERROR, 'bar', new Exception\PermissionDeniedException, 403],
            [ApiResponse::ERROR, 'bar', new \Exception, 500],
            [ApiResponse::ERROR, 'bar', new JsonException\RuntimeException, 400],
            ['foo', 'bar', null, 500]
        ];
    }

    /**
     * @dataProvider statusProvider
     */
    public function testStrategySetsStatus($apiStatus, $apiContent, $apiException, $httpStatus)
    {
        $apiResponse = $this->getMock('Omeka\Api\Response');
        $apiResponse->expects($this->once())
                    ->method('getStatus')
                    ->will($this->returnValue($apiStatus));
        $apiResponse->expects($this->any())
                    ->method('getContent')
                    ->will($this->returnValue($apiContent));

        $model = $this->getMock('Omeka\View\Model\ApiJsonModel');
        $model->expects($this->once())
              ->method('getApiResponse')
              ->will($this->returnValue($apiResponse));
        $model->expects($this->any())
              ->method('getException')
              ->will($this->returnValue($apiException));

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
        $this->assertEquals($expectedContentType, $headers->get('Content-Type')->getFieldValue());
    }
}
