<?php
namespace OmekaTest\View\Renderer;

use Omeka\Api\Exception\ValidationException;
use Omeka\View\Renderer\ApiJsonRenderer;
use Laminas\Json\Json;
use Omeka\Stdlib\ErrorStore;
use Omeka\Test\TestCase;

class ApiJsonRendererTest extends TestCase
{
    protected $eventManager;

    public function setUp(): void
    {
        $this->eventManager = $this->createMock('Laminas\EventManager\EventManager');
        $this->eventManager->expects($this->any())
            ->method('prepareArgs')
            ->will($this->returnCallback(function ($arg) {
                return $arg;
            })
        );
    }

    public function testRendererUsesApiResponse()
    {
        $testValue = ['test' => 'foo'];
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
                 ->method('getContent')
                 ->will($this->returnValue($testValue));

        $model = $this->createMock('Omeka\View\Model\ApiJsonModel');
        $model->expects($this->once())
              ->method('getApiResponse')
              ->will($this->returnValue($response));

        $renderer = new ApiJsonRenderer($this->eventManager);
        $this->assertEquals(Json::encode($testValue), $renderer->render($model));
    }

    public function testRendererPassesOnNullResponse()
    {
        $response = $this->createMock('Omeka\Api\Response');
        $response->expects($this->once())
                 ->method('getContent')
                 ->will($this->returnValue(null));

        $model = $this->createMock('Omeka\View\Model\ApiJsonModel');
        $model->expects($this->once())
              ->method('getApiResponse')
              ->will($this->returnValue($response));

        $renderer = new ApiJsonRenderer($this->eventManager);
        $this->assertEquals(null, $renderer->render($model));
    }

    public function testRendererShowsErrors()
    {
        $errorStore = new ErrorStore;
        $errorStore->addError('foo', 'bar');
        $exception = new ValidationException('exception message');
        $exception->setErrorStore($errorStore);

        $model = $this->createMock('Omeka\View\Model\ApiJsonModel');
        $model->expects($this->once())
              ->method('getApiResponse')
              ->will($this->returnValue(null));
        $model->expects($this->once())
              ->method('getException')
              ->will($this->returnValue($exception));

        $renderer = new ApiJsonRenderer($this->eventManager);
        $this->assertEquals(Json::encode(['errors' => ['foo' => ['bar']]]), $renderer->render($model));
    }
}
