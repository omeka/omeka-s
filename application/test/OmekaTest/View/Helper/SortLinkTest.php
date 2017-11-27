<?php
namespace OmekaTest\View\Helper;

use Omeka\View\Helper\SortLink;
use Omeka\Test\TestCase;

class SortLinkTest extends TestCase
{
    public function testInvoke()
    {
        $label = 'test-label';
        $sortBy = 'test-sortBy';

        $view = $this->getMockBuilder('Zend\View\Renderer\PhpRenderer')
            ->setMethods(['partial', 'url', 'params'])
            ->getMock();
        $view->expects($this->once())
            ->method('url')
            ->with(
                $this->equalTo(null),
                $this->equalTo([]),
                $this->equalTo([
                    'query' => [
                        'sort_by' => $sortBy,
                        'sort_order' => 'asc',
                    ],
                ]),
                $this->equalTo(true)
            );
        $view->expects($this->once())
            ->method('partial')
            ->with(
                $this->equalTo('common/sort-link'),
                $this->equalTo([
                    'label' => $label,
                    'url' => null,
                    'class' => 'sortable',
                    'sortBy' => $sortBy,
                    'sortOrder' => 'asc',
                ])
            );
        $params = $this->getMockBuilder('Omeka\View\Helper\Params')
            ->disableOriginalConstructor()
            ->getMock();
        $params->expects($this->exactly(3))
            ->method('fromQuery')
            ->will($this->returnValue([]));
        $view->expects($this->once())
            ->method('params')
            ->will($this->returnValue($params));

        $sortLink = new SortLink;
        $sortLink->setView($view);
        $sortLink->__invoke($label, $sortBy);
    }
}
