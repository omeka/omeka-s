<?php
namespace OmekaTest\View\Helper;

use Laminas\EventManager\EventManager;
use Laminas\Form\FormElementManager;
use Laminas\Form\View\Helper\FormSelect;
use Laminas\Form\View\Helper\FormHidden;
use Laminas\I18n\Translator\Translator;
use Laminas\I18n\View\Helper\Translate;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplatePathStack;
use Omeka\ColumnType;
use Omeka\Form\Element\BrowseDefaults;
use Omeka\Settings\SiteSettings;
use Omeka\Settings\UserSettings;
use Omeka\Stdlib\Browse as BrowseService;
use Omeka\Test\TestCase;
use Omeka\View\Helper\Browse;
use Omeka\View\Helper\AssetUrl;
use Omeka\View\Helper\Params;
use Omeka\View\Helper\QueryToHiddenInputs;
use Omeka\View\Helper\Status;
use Omeka\View\Helper\Trigger;

class BrowseTest extends TestCase
{
    /**
     * @dataProvider contextProvider
     */
    public function testSortSelectorTranslatesLabelsAndPreservesCustomHeaders($context)
    {
        $view = $this->getBrowseView($context, ['sort_by' => 'created', 'sort_order' => 'desc']);
        $markup = $view->browse()->renderSortSelector('items');

        $this->assertStringContainsString('<option value="created" selected>Créé</option>', $markup);
        $this->assertStringContainsString('<option value="resource_class_label">Classe de ressource</option>', $markup);
        $this->assertStringContainsString('<option value="owner_name">Title</option>', $markup);
        $this->assertStringContainsString('<option value="desc" selected>', $markup);
    }

    public function contextProvider()
    {
        return [['admin'], ['public']];
    }

    public function testSortSelectorTranslatesCustomSortLabelBeforeFormatting()
    {
        $view = $this->getBrowseView('admin', [], 'dcterms:<title>');
        $markup = $view->browse()->renderSortSelector('items');

        $this->assertStringContainsString(
            '<option value="dcterms:&lt;title&gt;">Personnalisé (dcterms:&lt;title&gt;)</option>',
            $markup
        );
    }

    public function testFulltextSortSelectorTranslatesRelevanceAndPreservesQuery()
    {
        $view = $this->getBrowseView('public', [
            'fulltext_search' => 'art & history',
            'sort_by' => 'created',
            'sort_order' => 'asc',
            'sort_by_default' => '1',
            'sort_order_default' => '1',
        ]);
        $markup = $view->browse()->renderSortSelector('items');

        $this->assertStringContainsString('<option value="" selected>Pertinence</option>', $markup);
        $this->assertStringContainsString('<option value="desc" selected>', $markup);
        $this->assertStringContainsString('name="fulltext_search" value="art &amp; history"', $markup);
        $this->assertStringNotContainsString('name="sort_by_default"', $markup);
    }

    public function testBrowseDefaultsFormTranslatesLabelsAndPreservesCustomHeaders()
    {
        $view = $this->getBrowseView('admin', []);
        $element = new BrowseDefaults('browse_defaults_admin_items');
        $element->setOptions([
            'browse_defaults_context' => 'admin',
            'browse_defaults_resource_type' => 'items',
        ]);
        $assetUrl = $this->createMock(AssetUrl::class);
        $assetUrl->method('__invoke')->willReturn('/asset');
        $view->getHelperPluginManager()->setService('assetUrl', $assetUrl);
        $formHidden = new FormHidden;
        $formHidden->setView($view);
        $view->getHelperPluginManager()->setService('formHidden', $formHidden);
        $markup = $view->partial('common/browse-defaults-form', ['element' => $element]);

        $this->assertStringContainsString('<option value="created">Créé</option>', $markup);
        $this->assertStringContainsString('<option value="owner_name">Title</option>', $markup);
        $this->assertStringNotContainsString('Translated twice', $markup);
    }

    public function testAdvancedSearchTranslatesLabelsOnlyOnce()
    {
        $view = $this->getBrowseView('admin', []);
        $sortConfig = $view->browse()->getBrowseService()->getSortConfig('admin', 'items');
        $this->assertSame('Created', $sortConfig['created']);
        $this->assertSame('Resource class', $sortConfig['resource_class_label']);
        $markup = $view->partial('common/advanced-search/sort', ['resourceType' => 'item', 'query' => []]);

        $this->assertStringContainsString('value="created">Créé</option>', $markup);
        $this->assertStringNotContainsString('Translated twice', $markup);
    }

    public function testSortSelectorPreservesEventLabels()
    {
        $view = $this->getBrowseView('admin', []);
        $view->browse()->getBrowseService()->getEventManager()->attach('sort-config', function ($event) {
            $args = $event->getParams();
            $args['sortConfig']['extension'] = 'Créé';
        });
        $markup = $view->browse()->renderSortSelector('items');

        $this->assertStringContainsString('<option value="extension">Créé</option>', $markup);
        $this->assertStringNotContainsString('Translated twice', $markup);
    }

    public function testSortSelectorEscapesCustomHeaders()
    {
        $view = $this->getBrowseView('admin', []);
        $view->browse()->getBrowseService()->getUserSettings()->method('get')->willReturnCallback(function ($name) {
            return 'columns_admin_items' === $name ? [['type' => 'owner', 'header' => '<em>Title & name</em>']] : null;
        });
        $markup = $view->browse()->renderSortSelector('items');

        $this->assertStringContainsString(
            '<option value="owner_name">&lt;em&gt;Title &amp; name&lt;/em&gt;</option>',
            $markup
        );
    }

    protected function getBrowseView($context, array $query, $defaultSort = 'created')
    {
        $view = new PhpRenderer;
        $view->setResolver(new TemplatePathStack(['script_paths' => [OMEKA_PATH . '/application/view']]));
        $helpers = $view->getHelperPluginManager();
        $translator = new Translator;
        $translator->setLocale('fr');
        $translator->addTranslationFile('gettext', OMEKA_PATH . '/application/language/fr.mo', 'default', 'fr');
        $translator->enableEventManager();
        $translator->getEventManager()->attach(Translator::EVENT_MISSING_TRANSLATION, function ($event) {
            return 'Créé' === $event->getParam('message') ? 'Translated twice' : null;
        });
        $translate = new Translate;
        $translate->setTranslator($translator);
        $helpers->setService('translate', $translate);
        $formSelect = new FormSelect;
        $formSelect->setTranslator($translator);
        $formSelect->setView($view);
        $helpers->setService('formSelect', $formSelect);

        $params = $this->createMock(Params::class);
        $params->method('fromQuery')->willReturnCallback(function ($name = null, $default = null) use ($query) {
            return null === $name ? $query : ($query[$name] ?? $default);
        });
        $helpers->setService('params', $params);
        $status = $this->createMock(Status::class);
        $status->method('isAdminRequest')->willReturn('admin' === $context);
        $helpers->setService('status', $status);
        $hiddenInputs = new QueryToHiddenInputs;
        $hiddenInputs->setView($view);
        $helpers->setService('queryToHiddenInputs', $hiddenInputs);
        $helpers->setService('trigger', new Trigger(new EventManager, new PluginManager(new ServiceManager)));

        $services = new ServiceManager(['services' => ['EventManager' => new EventManager, 'MvcTranslator' => $translator]]);
        $columns = new ColumnType\Manager($services, [
            'services' => ['created' => new ColumnType\Created, 'owner' => new ColumnType\Owner],
        ]);
        $browseService = new BrowseService(
            [$context => ['items' => [
                ['type' => 'created'],
                ['type' => 'owner', 'header' => 'Title'],
            ]]],
            [$context => ['items' => ['sort_by' => $defaultSort, 'sort_order' => 'desc']]],
            [$context => ['items' => ['resource_class_label' => 'Resource class']]],
            $columns,
            $helpers,
            new EventManager,
            $this->createMock(UserSettings::class),
            $this->createMock(SiteSettings::class)
        );
        $browse = new Browse($browseService, new FormElementManager(new ServiceManager));
        $browse->setView($view);
        $helpers->setService('browse', $browse);
        return $view;
    }
}
