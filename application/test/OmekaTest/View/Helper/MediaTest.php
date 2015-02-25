<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\TestCase;
use Omeka\View\Helper\Media;

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
    }

    public function testForm()
    {
        $this->mediaRepresentation->expects($this->once())
            ->method('type')
            ->will($this->returnValue('test'));
        $this->media = new Media($this->serviceManager);
        $form = $this->media->form($this->mediaRepresentation, $this->options);
        $this->assertEquals(serialize($this->options), $form);
    }

    public function testRender()
    {
        $this->mediaRepresentation->expects($this->once())
            ->method('type')
            ->will($this->returnValue('test'));
        $this->media = new Media($this->serviceManager);
        $render = $this->media->render($this->mediaRepresentation, $this->options);
        $this->assertEquals(serialize($this->options), $render);
    }
}
