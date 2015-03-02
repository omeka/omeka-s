<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\TestCase;
use Omeka\View\Helper\Media;
use Zend\View\Renderer\PhpRenderer;

require_once __DIR__ . '/_files/Renderer.php';

class MediaTest extends TestCase
{
    protected $serviceManager;

    protected $mediaRepresentation;

    protected $config = array(
        'media_types' => array(
            'test' => array(
                'renderer' => 'OmekaTest\Media\Renderer\Renderer',
            ),
        ),
    );

    protected $options = array('foo' => 'bar');

    public function setUp()
    {
        $this->serviceManager = $this->getServiceManager(
            array('Config' => $this->config)
        );
        $this->mediaRepresentation = $this->getMockBuilder('Omeka\Api\Representation\Entity\MediaRepresentation')
            ->disableOriginalConstructor()
            ->getMock();
        $view = new PhpRenderer;
        $this->media = new Media($this->serviceManager);
        $this->media->setView($view);
    }

    public function testForm()
    {
        $this->mediaRepresentation->expects($this->once())
            ->method('type')
            ->will($this->returnValue('test'));
        $form = $this->media->form($this->mediaRepresentation, $this->options);
        $this->assertEquals(serialize($this->options), $form);
    }

    public function testRender()
    {
        $this->mediaRepresentation->expects($this->once())
            ->method('type')
            ->will($this->returnValue('test'));
        $render = $this->media->render($this->mediaRepresentation, $this->options);
        $this->assertEquals(serialize($this->options), $render);
    }
}
