<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\TestCase;
use Omeka\View\Helper\Media;
use Zend\View\Renderer\PhpRenderer;

class MediaTest extends TestCase
{
    protected $serviceManager;

    public function setUp()
    {
        $testHandler = $this->getMock('Omeka\Media\Handler\FileHandler');

        $mediaManager = $this->getMock('Omeka\Media\Manager');
        $mediaManager->expects($this->once())
            ->method('get')
            ->with($this->equalTo('file'))
            ->will($this->returnValue($testHandler));

        $this->serviceManager = $this->getServiceManager(array(
            'Omeka\MediaManager' => $mediaManager,
        ));
    }

    public function testForm()
    {
        $media = new Media($this->serviceManager);
        $media->setView($this->getMock('Zend\View\Renderer\PhpRenderer'));
        $media->form('file', array());
    }

    public function testRender()
    {
        $media = new Media($this->serviceManager);
        $media->setView($this->getMock('Zend\View\Renderer\PhpRenderer'));
        $mediaRep = $this->getMockBuilder('Omeka\Api\Representation\Entity\MediaRepresentation')
            ->disableOriginalConstructor()
            ->getMock();
        $mediaRep->expects($this->once())
            ->method('type')
            ->will($this->returnValue('file'));
        $media->render($mediaRep, array());
    }
}
