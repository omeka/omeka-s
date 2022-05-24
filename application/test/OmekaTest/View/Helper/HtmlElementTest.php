<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\AbstractHttpControllerTestCase;

class HtmlElementTest extends AbstractHttpControllerTestCase
{
    public function testToString()
    {
        $element = 'test-element';
        $attributes = ['foo' => 'bar', 'baz' => 'bat'];

        $application = $this->getApplication();
        $viewHelperManager = $application->getServiceManager()->get('ViewHelperManager');
        $htmlElement = $viewHelperManager->get('htmlElement');

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

        $this->assertEquals('<test-element baz="bat&#x20;foo">', $return);

        $htmlElement($element)->removeAttributes();
        ob_start();
        echo $htmlElement($element);
        $return = ob_get_clean();

        $this->assertEquals('<test-element>', $return);
    }
}
