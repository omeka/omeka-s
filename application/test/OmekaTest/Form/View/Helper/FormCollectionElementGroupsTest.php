<?php
namespace OmekaTest\Form\View\Helper;

use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Form\View\Helper\FormCollectionElementGroups;
use Omeka\Test\TestCase;

class FormCollectionElementGroupsTest extends TestCase
{
    public function testRenderGroupsElements()
    {
        $form = new Form;
        $form->setOption('element_groups', [
            'first' => 'First',
            'second' => 'Second',
            'empty' => 'Empty',
        ]);
        $form->add(new Text('a', ['element_group' => 'second']));
        $form->add(new Text('b'));
        $form->add(new Text('c', ['element_group' => 'first']));
        $form->add(new Text('d', ['element_group' => 'unregistered']));
        $fieldset = new Fieldset('fieldset');
        $fieldset->add(new Text('e', ['element_group' => 'second']));
        $form->add($fieldset);

        $helper = new FormCollectionElementGroups;
        $helper->setView($this->getView());

        // Ungrouped elements come first, then groups in registered order.
        // Groups without elements are skipped, and fieldsets are flattened.
        $this->assertSame(
            '[b][d]'
            . '<fieldset><legend><h2 class="fieldsets-heading">First</h2></legend>[c]</fieldset>'
            . '<fieldset><legend><h2 class="fieldsets-heading">Second</h2></legend>[a][e]</fieldset>',
            $helper->render($form)
        );
    }

    /**
     * Get a view that renders each row as its element name in brackets.
     */
    protected function getView()
    {
        $view = $this->getMockBuilder(PhpRenderer::class)
            ->addMethods(['formRow', 'translate', 'escapeHtml'])
            ->getMock();
        $view->method('formRow')->willReturnCallback(fn ($element) => sprintf('[%s]', $element->getName()));
        $view->method('translate')->willReturnArgument(0);
        $view->method('escapeHtml')->willReturnArgument(0);
        return $view;
    }
}
