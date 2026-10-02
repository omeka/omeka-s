<?php
namespace OmekaTest\Form\View\Helper;

use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Form\View\Helper\FormCollectionSettingsGroups;
use Omeka\Test\TestCase;

class FormCollectionSettingsGroupsTest extends TestCase
{
    public function testRenderMarksModuleGroups()
    {
        $form = $this->getForm();
        $form->setOption('core_element_groups', ['general', 'display']);

        $this->assertSame(
            '[ungrouped]'
            . '<fieldset id="settings-group-general" class="settings-group"><legend><h2 class="fieldsets-heading">General</h2></legend>[a]</fieldset>'
            . '<fieldset id="settings-group-display" class="settings-group"><legend><h2 class="fieldsets-heading">Display</h2></legend>[b]</fieldset>'
            . '<fieldset id="settings-group-mapping" class="settings-group settings-group-module"><legend><h2 class="fieldsets-heading">Mapping</h2><span class="settings-group-module-label">Module</span></legend>[c]</fieldset>',
            $this->getHelper()->render($form)
        );
    }

    public function testRenderWithoutCoreGroupsOption()
    {
        $markup = $this->getHelper()->render($this->getForm());

        $this->assertStringContainsString('<fieldset id="settings-group-mapping" class="settings-group">', $markup);
        $this->assertStringNotContainsString('settings-group-module', $markup);
    }

    public function testRenderKeepsGroupOrder()
    {
        $form = $this->getForm();
        // A module group registered between core groups keeps its place.
        $form->setOption('element_groups', [
            'mapping' => 'Mapping',
            'general' => 'General',
            'display' => 'Display',
        ]);
        $form->setOption('core_element_groups', ['general', 'display']);

        $markup = $this->getHelper()->render($form);

        $this->assertLessThan(strpos($markup, 'settings-group-general'), strpos($markup, 'settings-group-mapping'));
        $this->assertLessThan(strpos($markup, 'settings-group-display'), strpos($markup, 'settings-group-general'));
    }

    public function testRenderFieldsetOptions()
    {
        // User settings register their groups on a fieldset, not the form.
        $fieldset = new Fieldset('user-settings');
        $fieldset->setOption('element_groups', [
            'general' => 'General',
            'zotero' => 'Zotero',
        ]);
        $fieldset->setOption('core_element_groups', ['general']);
        $fieldset->add(new Text('locale', ['element_group' => 'general']));
        $fieldset->add(new Text('zotero_key', ['element_group' => 'zotero']));

        $markup = $this->getHelper()->render($fieldset);

        $this->assertStringContainsString('<fieldset id="settings-group-general" class="settings-group">', $markup);
        $this->assertStringContainsString('<fieldset id="settings-group-zotero" class="settings-group settings-group-module">', $markup);
    }

    public function testRenderPutsUngroupedSettingsLast()
    {
        $form = $this->getForm();
        $form->setOption('core_element_groups', ['general', 'display']);
        $form->add(new Text('loose', ['label' => 'Loose']));
        $form->add(new Hidden('token'));

        $markup = $this->getHelper()->render($form);

        // Hidden and unlabeled elements stay first, outside any group.
        $this->assertStringStartsWith('[ungrouped][token]<fieldset', $markup);
        // Labeled ones go last, in a group of their own. Core settings are all
        // grouped, so it's labeled as a module's.
        $this->assertStringEndsWith(
            '<fieldset id="settings-group-other" class="settings-group settings-group-module"><legend><h2 class="fieldsets-heading">Other</h2><span class="settings-group-module-label">Module</span></legend>[loose]</fieldset>',
            $markup
        );
    }

    public function testRenderAddsUngroupedSettingsToARegisteredOtherGroup()
    {
        $form = $this->getForm();
        $form->setOption('element_groups', [
            'other' => 'Other things',
            'general' => 'General',
            'display' => 'Display',
            'mapping' => 'Mapping',
        ]);
        $form->setOption('core_element_groups', ['general', 'display']);
        $form->add(new Text('loose', ['label' => 'Loose']));

        $this->assertStringContainsString(
            '<fieldset id="settings-group-other" class="settings-group settings-group-module"><legend><h2 class="fieldsets-heading">Other things</h2><span class="settings-group-module-label">Module</span></legend>[loose]</fieldset><fieldset id="settings-group-general"',
            $this->getHelper()->render($form)
        );
    }

    public function testRenderKeepsUngroupedSettingsFirstWithoutCoreGroups()
    {
        // A theme's settings record no core groups, so settings without a
        // group stay first, where the theme put them.
        $form = $this->getForm();
        $form->add(new Text('loose', ['label' => 'Loose']));

        $markup = $this->getHelper()->render($form);

        $this->assertStringStartsWith('[ungrouped][loose]<fieldset', $markup);
        $this->assertStringNotContainsString('settings-group-other', $markup);
    }

    protected function getForm()
    {
        $form = new Form;
        $form->setOption('element_groups', [
            'general' => 'General',
            'display' => 'Display',
            'mapping' => 'Mapping',
        ]);
        $form->add(new Text('a', ['element_group' => 'general']));
        $form->add(new Text('ungrouped'));
        $form->add(new Text('b', ['element_group' => 'display']));
        $form->add(new Text('c', ['element_group' => 'mapping']));
        return $form;
    }

    /**
     * Get the helper, with a view that renders each row as its element name in
     * brackets.
     */
    protected function getHelper()
    {
        $view = $this->getMockBuilder(PhpRenderer::class)
            ->addMethods(['formRow', 'translate', 'escapeHtml', 'escapeHtmlAttr'])
            ->getMock();
        $view->method('formRow')->willReturnCallback(fn ($element) => sprintf('[%s]', $element->getName()));
        $view->method('translate')->willReturnArgument(0);
        $view->method('escapeHtml')->willReturnArgument(0);
        $view->method('escapeHtmlAttr')->willReturnArgument(0);
        $helper = new FormCollectionSettingsGroups;
        $helper->setView($view);
        return $helper;
    }
}
