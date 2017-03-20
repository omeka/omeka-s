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
}
