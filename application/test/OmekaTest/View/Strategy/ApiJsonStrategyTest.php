<?php
namespace OmekaTest\View\Strategy;

use Omeka\Api\Exception;
use Omeka\Mvc\Exception as MvcException;
use Omeka\View\Strategy\ApiJsonStrategy;
use Zend\Http\Response as HttpResponse;
use Zend\View\ViewEvent;
use Omeka\Test\TestCase;

class ApiJsonStrategyTest extends TestCase
{
    public $renderer;
    public $strategy;
    public $event;

    public function setUp()
    {
        $this->renderer = $this->createMock('Omeka\View\Renderer\ApiJsonRenderer');

        $this->strategy = new ApiJsonStrategy($this->renderer);

        $this->event = new ViewEvent;
        $httpResponse = new HttpResponse;
        $this->event->setResponse($httpResponse);
        $this->event->setResult('{}');
    }

    public function testStrategyPicksRendererForApiJsonModel()
    {
        $model = $this->createMock('Omeka\View\Model\ApiJsonModel');

        $this->event->setModel($model);

        $this->assertSame($this->renderer, $this->strategy->selectRenderer($this->event));
    }

    public function testStrategyDoesNothingForOtherModels()
    {
        $model = $this->createMock('Zend\View\Model\JsonModel');
        $model->expects($this->never())
              ->method('getOption');

        $this->event->setModel($model);

        $this->assertNull($this->strategy->selectRenderer($this->event));
        $this->strategy->injectResponse($this->event);
    }

    public function statusProvider()
    {
        return [
            ['bar', null, 200],
            [null, null, 204],
            ['bar', new Exception\ValidationException, 422],
            ['bar', new Exception\NotFoundException, 404],
            ['bar', new Exception\PermissionDeniedException, 403],
            ['bar', new \Exception, 500],
            ['bar', new MvcException\InvalidJsonException, 400],
        ];
    }

    /**
     * @dataProvider statusProvider
     */
    public function testStrategySetsStatus($apiContent, $apiException, $httpStatus)
    {
        if (!$apiException) {
            $apiResponse = $this->createMock('Omeka\Api\Response');
            $apiResponse->expects($this->any())
                        ->method('getContent')
                        ->will($this->returnValue($apiContent));
        } else {
            $apiResponse = null;
        }

        $model = $this->createMock('Omeka\View\Model\ApiJsonModel');
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
        $apiResponse = $this->createMock('Omeka\Api\Response');

        $model = $this->createMock('Omeka\View\Model\ApiJsonModel');
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
