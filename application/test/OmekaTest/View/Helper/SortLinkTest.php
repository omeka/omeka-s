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

        $view = $this->getMock(
            'Zend\View\Renderer\PhpRenderer',
            array('partial', 'url')
        );
        $view->expects($this->once())
            ->method('url')
            ->with(
                $this->equalTo(null),
                $this->equalTo(array()),
                $this->equalTo(array(
                    'query' => array(
                        'sort_by' => $sortBy,
                        'sort_order' => 'asc'
                    )
                )),
                $this->equalTo(true)
            );
        $view->expects($this->once())
            ->method('partial')
            ->with(
                $this->equalTo('common/sort-link'),
                $this->equalTo(array(
                    'label' => $label,
                    'url' => null,
                    'class' => 'sortable',
                    'sortBy' => $sortBy,
                    'sortOrder' => 'asc',
                ))
            );

        $sortLink = new SortLink;
        $sortLink->setView($view);
        $sortLink->__invoke($label, $sortBy);
    }
}
