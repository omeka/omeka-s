<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\TestCase;
use Omeka\View\Helper\HtmlElement;

class HtmlElementTest extends TestCase
{
    protected $htmlElement;

    public function setUp()
    {
        $view = $this->createMock('Zend\View\Renderer\PhpRenderer');
        $view->expects($this->any())
            ->method('plugin')
            ->will($this->returnValue('htmlspecialchars'));

        $this->htmlElement = new HtmlElement;
        $this->htmlElement->setView($view);
    }

    public function testToString()
    {
        $element = 'test-element';
        $attributes = ['foo' => 'bar', 'baz' => 'bat'];

        $htmlElement = $this->htmlElement;
        $htmlElement($element)->setAttributes($attributes);
        ob_start();
        echo $htmlElement($element);
        $return = ob_get_clean();

        $this->assertEquals('<test-element foo="bar" baz="bat">', $return);

        $htmlElement($element)->removeAttribute('foo');
        ob_start();
        echo $htmlElement($element);
        $return = ob_get_clean();

        $this->assertEquals('<test-element baz="bat">', $return);

        $htmlElement($element)->appendAttribute('baz', 'foo');
        ob_start();
        echo $htmlElement($element);
        $return = ob_get_clean();

        $this->assertEquals('<test-element baz="bat foo">', $return);

        $htmlElement($element)->removeAttributes();
        ob_start();
        echo $htmlElement($element);
        $return = ob_get_clean();

        $this->assertEquals('<test-element>', $return);
    }
}
