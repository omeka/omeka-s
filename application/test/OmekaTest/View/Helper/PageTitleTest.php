<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\TestCase;
use Omeka\View\Helper\PageTitle;

class PageTitleTest extends TestCase
{
    public function testPageTitle()
    {
        $title = 'A Title';
        $level = 1;
        $subhead = 'Section';
        $action = 'Action';

        $view = $this->getMockBuilder('Laminas\View\Renderer\PhpRenderer')
            ->setMethods(['escapeHtml', 'headTitle', 'partial'])
            ->getMock();
        $view->expects($this->any())
            ->method('escapeHtml')
            ->will($this->returnArgument(0));
        $view->expects($this->exactly(3))
            ->method('headTitle')
            ->withConsecutive([$subhead], [$title], [$action]);
        $view->expects($this->once())
            ->method('partial')
            ->with(
                $this->equalTo('common/page-title'),
                $this->equalTo([
                    'title' => $title,
                    'level' => 1,
                    'subheadLabel' => $subhead,
                    'actionLabel' => $action,
                ])
            );

        $helper = new PageTitle;
        $helper->setView($view);
        $helper->__invoke($title, $level, $subhead, $action);
    }
}
