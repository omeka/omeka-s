<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\TestCase;
use Omeka\View\Helper\PageTitle;

class PageTitleTest extends TestCase
{
    public function testPageTitle()
    {
        $title = 'A Title';
        $subhead = 'Section';
        $action = 'Action';

        $view = $this->getMockBuilder('Zend\View\Renderer\PhpRenderer')
            ->setMethods(['escapeHtml', 'headTitle'])
            ->getMock();
        $view->expects($this->any())
            ->method('escapeHtml')
            ->will($this->returnArgument(0));
        $view->expects($this->exactly(3))
            ->method('headTitle')
            ->withConsecutive([$subhead], [$title], [$action]);

        $helper = new PageTitle;
        $helper->setView($view);
        $this->assertEquals("<h1><span class=\"subhead\">$subhead</span><span class=\"title\">$title</span><span class=\"action\">$action</span></h1>", $helper($title, 1, $subhead, $action));
    }
}
