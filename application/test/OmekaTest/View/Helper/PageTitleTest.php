<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\TestCase;
use Omeka\View\Helper\PageTitle;

class PageTitleTest extends TestCase
{
    public function testPageTitle()
    {
        $title = 'A Title';

        $view = $this->getMock('Zend\View\Renderer\PhpRenderer',
            ['escapeHtml', 'headTitle']
        );
        $view->expects($this->once())
            ->method('escapeHtml')
            ->with($title)
            ->will($this->returnArgument(0));
        $view->expects($this->once())
            ->method('headTitle')
            ->with($title);

        $helper = new PageTitle;
        $helper->setView($view);
        $this->assertEquals("<h1>$title</h1>", $helper($title));
    }
}
