<?php
namespace OmekaTest\Stdlib;

use InvalidArgumentException;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Module\Manager as ModuleManager;
use Omeka\Module\Module;
use Omeka\ServiceManager\AbstractPluginManager;
use Omeka\Settings\FallbackSettings;
use Omeka\Settings\Settings;
use Omeka\Settings\SiteSettings;
use Omeka\Settings\UserSettings;
use Omeka\Site\BlockLayout;
use Omeka\Site\BlockLayout\BlockLayoutInterface;
use Omeka\Stdlib\OptionSidebar;
use Omeka\Test\TestCase;
use ReflectionMethod;

class OptionSidebarTest extends TestCase
{
    protected $services = [];
    protected $modulesByClass = [];
    protected $fallbackSettings;
    protected $settings;
    protected $siteSettings;
    protected $userSettings;

    public function setUp(): void
    {
        $this->fallbackSettings = $this->createMock(FallbackSettings::class);
        $this->settings = $this->createMock(Settings::class);
        $this->siteSettings = $this->createMock(SiteSettings::class);
        $this->userSettings = $this->createMock(UserSettings::class);
    }

    public function testGroupsOptionsByCategoryThenCoreThenModuleThenOther()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->addCoreOption('pageTitle', new BlockLayout\PageTitle);
        $this->addModuleOption('map', 'Map', 'Mapping', 'Mapping');
        $this->addOtherOption('library', 'Library block');
        $sidebar = $this->getOptionSidebar([
            'categories' => ['text' => ['label' => 'Text', 'position' => 10]],
            'category_names' => ['pageTitle' => 'text'],
        ]);

        $groups = $sidebar->getGroups('test');
        $this->assertSame(['category:text', 'core', 'module:Mapping', 'other'], array_column($groups, 'key'));
        $this->assertSame(['Text', 'Core', 'Mapping', 'Other'], array_column($groups, 'label'));
        $this->assertSame(['pageTitle'], array_column($groups[0]['options'], 'name'));
        $this->assertSame(['lineBreak'], array_column($groups[1]['options'], 'name'));
        $this->assertSame(['map'], array_column($groups[2]['options'], 'name'));
        $this->assertSame(['library'], array_column($groups[3]['options'], 'name'));
    }

    public function testOrdersCategoriesByPositionThenLabel()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->addCoreOption('pageTitle', new BlockLayout\PageTitle);
        $this->addCoreOption('tableOfContents', new BlockLayout\TableOfContents);
        $sidebar = $this->getOptionSidebar([
            'categories' => [
                'b' => ['label' => 'Bravo', 'position' => 20],
                'a' => ['label' => 'Alpha', 'position' => 20],
                'c' => ['label' => 'Charlie', 'position' => 10],
            ],
            'category_names' => ['lineBreak' => 'b', 'pageTitle' => 'a', 'tableOfContents' => 'c'],
        ]);

        $this->assertSame(['Charlie', 'Alpha', 'Bravo'], array_column($sidebar->getGroups('test'), 'label'));
    }

    public function testOrdersModuleGroupsByModuleNameAndOptionsByLabel()
    {
        $this->addModuleOption('zooB', 'Zoo B', 'Zoo', 'Zoo module');
        $this->addModuleOption('zooA', 'Zoo A', 'Zoo', 'Zoo module');
        $this->addModuleOption('apple', 'Apple', 'Apple', 'Apple module');
        $sidebar = $this->getOptionSidebar([]);

        $groups = $sidebar->getGroups('test');
        $this->assertSame(['Apple module', 'Zoo module'], array_column($groups, 'label'));
        $this->assertSame(['Zoo A', 'Zoo B'], array_column($groups[1]['options'], 'label'));
    }

    public function testSetsModuleNameOnModuleOptionsOnly()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->addModuleOption('map', 'Map', 'Mapping', 'Mapping');
        $this->addOtherOption('library', 'Library block');
        $sidebar = $this->getOptionSidebar([
            'categories' => ['media' => ['label' => 'Media', 'position' => 10]],
            'category_names' => ['map' => 'media'],
        ]);

        $modules = [];
        foreach ($sidebar->getGroups('test') as $group) {
            foreach ($group['options'] as $option) {
                $modules[$option['name']] = $option['module'];
            }
        }
        $this->assertSame(['map' => 'Mapping', 'lineBreak' => null, 'library' => null], $modules);
    }

    public function testNamesMappedToAnUndeclaredCategoryFallThrough()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $sidebar = $this->getOptionSidebar(['category_names' => ['lineBreak' => 'missing']]);

        $this->assertSame(['core'], array_column($sidebar->getGroups('test'), 'key'));
    }

    public function testLeavesOutExcludedNames()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->addCoreOption('blockGroup', new BlockLayout\PageTitle);
        $sidebar = $this->getOptionSidebar([], ['exclude' => ['blockGroup']]);

        $this->assertSame(['lineBreak'], $sidebar->getNames('test'));
        $this->assertSame(['lineBreak'], array_column($sidebar->getGroups('test')[0]['options'], 'name'));
    }

    public function testDefaultArrangementComesFromConfig()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->addCoreOption('pageTitle', new BlockLayout\PageTitle);
        $this->addCoreOption('tableOfContents', new BlockLayout\TableOfContents);
        $this->fallbackSettings->method('getWithSource')->willReturn(['value' => null, 'source' => null]);
        $sidebar = $this->getOptionSidebar([], [
            'pinned' => [
                'tableOfContents' => 20,
                'lineBreak' => 0,
                'pageTitle' => false,
                'unknown' => 5,
            ],
        ]);

        $this->assertSame(
            ['pinned' => ['lineBreak', 'tableOfContents'], 'hidden' => [], 'source' => 'default'],
            $sidebar->getArrangement('test')
        );
    }

    public function testArrangementComesFromTheFirstLevelWithAValue()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->addCoreOption('pageTitle', new BlockLayout\PageTitle);
        $this->fallbackSettings->expects($this->exactly(2))
            ->method('getWithSource')
            ->withConsecutive(
                ['option_sidebar_test', ['user'], null, ['site' => 5]],
                ['option_sidebar_test', ['site'], null, ['site' => 5]]
            )
            ->willReturnOnConsecutiveCalls(
                ['value' => null, 'source' => null],
                ['value' => ['pinned' => ['pageTitle'], 'hidden' => ['lineBreak']], 'source' => 'site']
            );
        $sidebar = $this->getOptionSidebar([]);

        $this->assertSame(
            ['pinned' => ['pageTitle'], 'hidden' => ['lineBreak'], 'source' => 'site'],
            $sidebar->getArrangement('test', 5)
        );
    }

    public function testEmptyArrangementIsHonored()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->fallbackSettings->method('getWithSource')
            ->willReturn(['value' => ['pinned' => [], 'hidden' => []], 'source' => 'user']);
        $sidebar = $this->getOptionSidebar([], ['pinned' => ['lineBreak' => 10]]);

        $this->assertSame(['pinned' => [], 'hidden' => [], 'source' => 'user'], $sidebar->getArrangement('test'));
    }

    public function testMalformedValueIsTreatedAsUnset()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->fallbackSettings->method('getWithSource')->willReturnOnConsecutiveCalls(
            ['value' => 'not an arrangement', 'source' => 'user'],
            ['value' => ['pinned' => 'lineBreak'], 'source' => 'site']
        );
        $sidebar = $this->getOptionSidebar([], ['pinned' => ['lineBreak' => 10]]);

        $this->assertSame('default', $sidebar->getArrangement('test')['source']);
    }

    public function testNormalizeKeepsKnownUniqueNamesAndUnpinsHiddenNames()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->addCoreOption('pageTitle', new BlockLayout\PageTitle);
        $this->addCoreOption('tableOfContents', new BlockLayout\TableOfContents);
        $sidebar = $this->getOptionSidebar([]);

        $this->assertSame(
            ['pinned' => ['pageTitle', 'lineBreak'], 'hidden' => ['tableOfContents']],
            $sidebar->normalize(
                'test',
                ['pageTitle', 'unknown', 'pageTitle', 3, 'tableOfContents', 'lineBreak'],
                ['tableOfContents', 'unknown', 'tableOfContents']
            )
        );
        $this->assertSame(['pinned' => [], 'hidden' => []], $sidebar->normalize('test', 'lineBreak', null));
    }

    public function testThrowsForAnUnknownSidebar()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getOptionSidebar([])->getGroups('missing');
    }

    public function testSavesForTheUser()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->addCoreOption('pageTitle', new BlockLayout\PageTitle);
        $this->userSettings->expects($this->once())
            ->method('set')
            ->with('option_sidebar_test', ['pinned' => ['pageTitle'], 'hidden' => ['lineBreak']]);
        $this->userSettings->expects($this->never())->method('delete');
        $this->siteSettings->expects($this->never())->method($this->anything());
        $this->settings->expects($this->never())->method($this->anything());

        $this->getOptionSidebar([])->save(
            'test',
            'user',
            ['pinned' => ['pageTitle', 'unknown'], 'hidden' => ['lineBreak']]
        );
    }

    public function testDeletesForTheUser()
    {
        $this->userSettings->expects($this->once())->method('delete')->with('option_sidebar_test');
        $this->userSettings->expects($this->never())->method('set');

        $this->getOptionSidebar([])->save('test', 'user', null);
    }

    public function testSavesForASiteAndClearsTheUsersOwnArrangement()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->siteSettings->expects($this->once())
            ->method('set')
            ->with('option_sidebar_test', ['pinned' => ['lineBreak'], 'hidden' => []], 5);
        $this->userSettings->expects($this->once())->method('delete')->with('option_sidebar_test');

        $this->getOptionSidebar([])->save('test', 'site', ['pinned' => ['lineBreak']], 5);
    }

    public function testDeletesForASiteAndClearsTheUsersOwnArrangement()
    {
        $this->siteSettings->expects($this->once())->method('delete')->with('option_sidebar_test', 5);
        $this->userSettings->expects($this->once())->method('delete')->with('option_sidebar_test');

        $this->getOptionSidebar([])->save('test', 'site', null, 5);
    }

    public function testSavesGloballyAndClearsTheUsersOwnArrangement()
    {
        $this->settings->expects($this->once())
            ->method('set')
            ->with('option_sidebar_test', ['pinned' => [], 'hidden' => []]);
        $this->userSettings->expects($this->once())->method('delete')->with('option_sidebar_test');

        $this->getOptionSidebar([], ['levels' => ['user', 'global']])->save('test', 'global', []);
    }

    public function testRejectsALevelTheSidebarDoesNotHave()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getOptionSidebar([])->save('test', 'global', []);
    }

    public function testRejectsASiteSaveWithoutASite()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getOptionSidebar([])->save('test', 'site', []);
    }

    public function testGetModuleForClass()
    {
        $moduleManager = new ModuleManager($this->createMock(ServiceLocatorInterface::class));
        $moduleManager->registerModule('Foo')->setState(ModuleManager::STATE_ACTIVE);
        $moduleManager->registerModule('Bar')->setState(ModuleManager::STATE_NOT_ACTIVE);
        $sidebar = new OptionSidebar(
            [],
            [],
            $moduleManager,
            $this->fallbackSettings,
            $this->settings,
            $this->siteSettings,
            $this->userSettings,
            $this->getTranslator()
        );
        $method = new ReflectionMethod($sidebar, 'getModuleForClass');
        $method->setAccessible(true);

        $this->assertSame('Foo', $method->invoke($sidebar, 'Foo\Site\BlockLayout\Baz')->getId());
        $this->assertNull($method->invoke($sidebar, 'Bar\Site\BlockLayout\Baz'));
        $this->assertNull($method->invoke($sidebar, 'Unregistered\Site\BlockLayout\Baz'));
        $this->assertNull($method->invoke($sidebar, 'Omeka\Site\BlockLayout\Html'));
        $this->assertNull($method->invoke($sidebar, 'NoNamespace'));
        $this->assertNull($method->invoke($sidebar, 'foo\Site\BlockLayout\Baz'));
    }

    public function testEveryCoreBlockLayoutHasACategory()
    {
        $config = require OMEKA_PATH . '/application/config/module.config.php';
        $blockLayouts = $config['block_layouts'];
        $names = array_merge(array_keys($blockLayouts['invokables']), array_keys($blockLayouts['factories']));
        foreach (array_diff($names, $config['option_sidebars']['block_layouts']['exclude']) as $name) {
            $this->assertArrayHasKey($name, $blockLayouts['category_names'], $name);
            $this->assertArrayHasKey($blockLayouts['category_names'][$name], $blockLayouts['categories'], $name);
        }
    }

    /**
     * Build the service with one sidebar, "test", whose options are the ones
     * added by the add*Option() methods.
     */
    protected function getOptionSidebar(array $managerConfig, array $spec = []): OptionSidebar
    {
        $manager = $this->createMock(AbstractPluginManager::class);
        $manager->method('getRegisteredNames')->willReturn(array_keys($this->services));
        $manager->method('get')->willReturnCallback(function ($name) {
            return $this->services[$name];
        });
        $config = [
            'option_sidebars' => ['test' => $spec + ['levels' => ['user', 'site']]],
            'test' => $managerConfig,
        ];
        $sidebar = $this->getMockBuilder(OptionSidebar::class)
            ->setConstructorArgs([
                $config,
                ['test' => $manager],
                $this->createMock(ModuleManager::class),
                $this->fallbackSettings,
                $this->settings,
                $this->siteSettings,
                $this->userSettings,
                $this->getTranslator(),
            ])
            ->onlyMethods(['getModuleForClass'])
            ->getMock();
        $sidebar->method('getModuleForClass')->willReturnCallback(function ($class) {
            return $this->modulesByClass[$class] ?? null;
        });
        return $sidebar;
    }

    protected function getTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('translate')->willReturnArgument(0);
        return $translator;
    }

    protected function addCoreOption(string $name, BlockLayoutInterface $layout): void
    {
        $this->services[$name] = $layout;
    }

    /**
     * Add an option whose class the stubbed getModuleForClass() maps to a
     * module.
     */
    protected function addModuleOption(string $name, string $label, string $moduleId, string $moduleName): void
    {
        $layout = $this->createLayout($label);
        $module = new Module($moduleId);
        $module->setIni(['name' => $moduleName]);
        $module->setState(ModuleManager::STATE_ACTIVE);
        $this->services[$name] = $layout;
        $this->modulesByClass[get_class($layout)] = $module;
    }

    /**
     * Add an option whose class is outside the Omeka namespace and maps to no
     * module.
     */
    protected function addOtherOption(string $name, string $label): void
    {
        $this->services[$name] = $this->createLayout($label);
    }

    /**
     * Create a mock layout with a class name of its own.
     *
     * Identical createMock() calls share one mock class, and the stubbed
     * getModuleForClass() tells options apart by class name.
     */
    protected function createLayout(string $label): BlockLayoutInterface
    {
        static $count = 0;
        $layout = $this->getMockBuilder(BlockLayoutInterface::class)
            ->setMockClassName(sprintf('OptionSidebarTestLayout%d', ++$count))
            ->getMock();
        $layout->method('getLabel')->willReturn($label);
        return $layout;
    }
}
