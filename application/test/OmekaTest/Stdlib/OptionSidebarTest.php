<?php
namespace OmekaTest\Stdlib;

use InvalidArgumentException;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Module\Manager as ModuleManager;
use Omeka\Module\Module;
use Omeka\Permissions\Acl;
use Omeka\ServiceManager\AbstractPluginManager;
use Omeka\Service\Exception\RuntimeException;
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
    protected $settings;
    protected $siteSettings;
    protected $userSettings;
    protected $acl;

    public function setUp(): void
    {
        $this->settings = $this->createMock(Settings::class);
        $this->siteSettings = $this->createMock(SiteSettings::class);
        $this->userSettings = $this->createMock(UserSettings::class);
        $this->acl = $this->createMock(Acl::class);
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
            $sidebar->getArrangement('test', 5)
        );
    }

    public function testArrangementComesFromTheFirstLevelWithAValue()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->addCoreOption('pageTitle', new BlockLayout\PageTitle);
        $this->userSettings->expects($this->once())
            ->method('get')
            ->with('option_sidebar_test_site_5')
            ->willReturn(null);
        $this->siteSettings->expects($this->once())
            ->method('get')
            ->with('option_sidebar_test', null, 5)
            ->willReturn(['pinned' => ['pageTitle'], 'hidden' => ['lineBreak']]);
        $sidebar = $this->getOptionSidebar([]);

        $this->assertSame(
            ['pinned' => ['pageTitle'], 'hidden' => ['lineBreak'], 'source' => 'site'],
            $sidebar->getArrangement('test', 5)
        );
    }

    public function testUsersOwnArrangementIsPerSite()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->userSettings->expects($this->once())
            ->method('get')
            ->with('option_sidebar_test_site_7')
            ->willReturn(['pinned' => ['lineBreak']]);
        $this->siteSettings->expects($this->never())->method('get');
        $sidebar = $this->getOptionSidebar([]);

        $this->assertSame(
            ['pinned' => ['lineBreak'], 'hidden' => [], 'source' => 'user'],
            $sidebar->getArrangement('test', 7)
        );
    }

    public function testPerSiteSidebarWithoutASiteReadsNoUserOrSiteArrangement()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->userSettings->expects($this->never())->method('get');
        $this->siteSettings->expects($this->never())->method('get');

        $this->assertSame('default', $this->getOptionSidebar([])->getArrangement('test')['source']);
    }

    public function testSidebarWithoutASiteLevelHasOneUserArrangement()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->userSettings->expects($this->once())
            ->method('get')
            ->with('option_sidebar_test')
            ->willReturn(['pinned' => ['lineBreak']]);
        $sidebar = $this->getOptionSidebar([], ['levels' => ['user', 'global']]);

        $this->assertSame('user', $sidebar->getArrangement('test', 5)['source']);
    }

    public function testEmptyArrangementIsHonored()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->userSettings->method('get')->willReturn(['pinned' => [], 'hidden' => []]);
        $sidebar = $this->getOptionSidebar([], ['pinned' => ['lineBreak' => 10]]);

        $this->assertSame(['pinned' => [], 'hidden' => [], 'source' => 'user'], $sidebar->getArrangement('test', 5));
    }

    public function testNullAndEmptyStringFallThrough()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->userSettings->method('get')->willReturn('');
        $this->siteSettings->method('get')->willReturn(['pinned' => ['lineBreak']]);

        $this->assertSame('site', $this->getOptionSidebar([])->getArrangement('test', 5)['source']);
    }

    public function testMalformedValueFallsThroughToTheNextLevel()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->userSettings->method('get')->willReturn('not an arrangement');
        $this->siteSettings->method('get')->willReturn(['pinned' => 'lineBreak']);
        $sidebar = $this->getOptionSidebar([], ['pinned' => ['lineBreak' => 10]]);

        $this->assertSame('default', $sidebar->getArrangement('test', 5)['source']);
    }

    public function testNoLoggedInUserFallsThroughToTheSite()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->userSettings->method('get')
            ->willThrowException(new RuntimeException('Cannot manage settings when no target ID is set.'));
        $this->siteSettings->method('get')->willReturn(['pinned' => ['lineBreak']]);

        $this->assertSame('site', $this->getOptionSidebar([])->getArrangement('test', 5)['source']);
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

    public function testSavesForTheUserOnThisSite()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->addCoreOption('pageTitle', new BlockLayout\PageTitle);
        $this->userSettings->expects($this->once())
            ->method('set')
            ->with('option_sidebar_test_site_5', ['pinned' => ['pageTitle'], 'hidden' => ['lineBreak']]);
        $this->userSettings->expects($this->never())->method('delete');
        $this->siteSettings->expects($this->never())->method($this->anything());
        $this->settings->expects($this->never())->method($this->anything());

        $this->getOptionSidebar([])->save(
            'test',
            'user',
            ['pinned' => ['pageTitle', 'unknown'], 'hidden' => ['lineBreak']],
            5
        );
    }

    public function testDeletesForTheUserOnThisSite()
    {
        $this->userSettings->expects($this->once())->method('delete')->with('option_sidebar_test_site_5');
        $this->userSettings->expects($this->never())->method('set');

        $this->getOptionSidebar([])->save('test', 'user', null, 5);
    }

    public function testSavesForASiteAndLeavesTheUsersOwnArrangement()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->siteSettings->expects($this->once())
            ->method('set')
            ->with('option_sidebar_test', ['pinned' => ['lineBreak'], 'hidden' => []], 5);
        $this->userSettings->expects($this->never())->method($this->anything());

        $this->getOptionSidebar([])->save('test', 'site', ['pinned' => ['lineBreak']], 5);
    }

    public function testDeletesForASiteAndLeavesTheUsersOwnArrangement()
    {
        $this->siteSettings->expects($this->once())->method('delete')->with('option_sidebar_test', 5);
        $this->userSettings->expects($this->never())->method($this->anything());

        $this->getOptionSidebar([])->save('test', 'site', null, 5);
    }

    public function testSavesForTheUserOnASidebarWithoutASiteLevel()
    {
        $this->userSettings->expects($this->once())
            ->method('set')
            ->with('option_sidebar_test', ['pinned' => [], 'hidden' => []]);

        $this->getOptionSidebar([], ['levels' => ['user', 'global']])->save('test', 'user', []);
    }

    public function testSavesGloballyAndLeavesTheUsersOwnArrangement()
    {
        $this->settings->expects($this->once())
            ->method('set')
            ->with('option_sidebar_test', ['pinned' => [], 'hidden' => []]);
        $this->userSettings->expects($this->never())->method($this->anything());

        $this->getOptionSidebar([], ['levels' => ['user', 'global']])->save('test', 'global', []);
    }

    public function testRejectsALevelTheSidebarDoesNotHave()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getOptionSidebar([])->save('test', 'global', [], 5);
    }

    public function testRejectsASaveWithoutASiteOnAPerSiteSidebar()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getOptionSidebar([])->save('test', 'user', []);
    }

    public function testReadsTheOptionNamesOnce()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $sidebar = $this->getOptionSidebar([]);
        $manager = $this->createMock(AbstractPluginManager::class);
        $manager->expects($this->once())->method('getRegisteredNames')->willReturn(['lineBreak']);
        $manager->method('get')->willReturn(new BlockLayout\LineBreak);
        $property = new \ReflectionProperty($sidebar, 'managers');
        $property->setAccessible(true);
        $property->setValue($sidebar, ['test' => $manager]);

        $sidebar->getGroups('test');
        $sidebar->normalize('test', ['lineBreak'], []);
        $sidebar->getDefaultArrangement('test');
    }

    public function testSharedArrangementIgnoresTheUsersOwn()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->addCoreOption('pageTitle', new BlockLayout\PageTitle);
        $this->userSettings->method('get')->willReturn(['pinned' => ['pageTitle']]);
        $this->siteSettings->method('get')->with('option_sidebar_test', null, 5)
            ->willReturn(['pinned' => ['lineBreak']]);
        $sidebar = $this->getOptionSidebar([]);

        $this->assertSame(
            ['pinned' => ['lineBreak'], 'hidden' => [], 'source' => 'site'],
            $sidebar->getSharedArrangement('test', 5)
        );
    }

    public function testSharedArrangementFallsBackToTheDefault()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $sidebar = $this->getOptionSidebar([], ['pinned' => ['lineBreak' => 10]]);

        $this->assertSame(
            ['pinned' => ['lineBreak'], 'hidden' => [], 'source' => 'default'],
            $sidebar->getSharedArrangement('test', 5)
        );
    }

    public function testArrangementsByScope()
    {
        $this->addCoreOption('lineBreak', new BlockLayout\LineBreak);
        $this->addCoreOption('pageTitle', new BlockLayout\PageTitle);
        $this->userSettings->method('get')->willReturn(['pinned' => ['pageTitle']]);
        $this->siteSettings->method('get')->willReturn(['pinned' => ['lineBreak']]);
        $sidebar = $this->getOptionSidebar([]);

        $this->assertSame(['user'], array_keys($sidebar->getArrangementsByScope('test', 5, false)));
        $arrangements = $sidebar->getArrangementsByScope('test', 5, true);
        $this->assertSame(['pageTitle'], $arrangements['user']['pinned']);
        $this->assertSame(['lineBreak'], $arrangements['site']['pinned']);
    }

    public function testFilterLabelComesFromConfigWithAGenericDefault()
    {
        $this->assertSame('Filter blocks', $this->getOptionSidebar([], ['filter_label' => 'Filter blocks'])->getFilterLabel('test'));
        $this->assertSame('Filter options', $this->getOptionSidebar([])->getFilterLabel('test'));
    }

    public function testSharedLevelIsTheFirstLevelAfterTheUsers()
    {
        $this->assertSame('site', $this->getOptionSidebar([])->getSharedLevel('test'));
        $this->assertSame('global', $this->getOptionSidebar([], ['levels' => ['user', 'global']])->getSharedLevel('test'));
        $this->assertNull($this->getOptionSidebar([], ['levels' => ['user']])->getSharedLevel('test'));
    }

    public function testAnyoneCanSaveTheirOwnArrangement()
    {
        $this->assertTrue($this->getOptionSidebar([])->canSave('test', 'user'));
    }

    public function testSavingForASiteNeedsPermissionToUpdateIt()
    {
        $allowed = $this->createMock(SiteRepresentation::class);
        $allowed->method('userIsAllowed')->with('update')->willReturn(true);
        $denied = $this->createMock(SiteRepresentation::class);
        $denied->method('userIsAllowed')->with('update')->willReturn(false);
        $sidebar = $this->getOptionSidebar([]);

        $this->assertTrue($sidebar->canSave('test', 'site', $allowed));
        $this->assertFalse($sidebar->canSave('test', 'site', $denied));
        $this->assertFalse($sidebar->canSave('test', 'site'));
    }

    public function testSavingForEveryoneNeedsTheGlobalSettings()
    {
        $this->acl->method('userIsAllowed')
            ->with('Omeka\\Controller\\Admin\\Setting', 'browse')
            ->willReturnOnConsecutiveCalls(true, false);
        $sidebar = $this->getOptionSidebar([], ['levels' => ['user', 'global']]);

        $this->assertTrue($sidebar->canSave('test', 'global'));
        $this->assertFalse($sidebar->canSave('test', 'global'));
    }

    public function testCannotSaveAtALevelTheSidebarDoesNotHave()
    {
        $this->acl->method('userIsAllowed')->willReturn(true);

        $this->assertFalse($this->getOptionSidebar([])->canSave('test', 'global'));
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
            $this->settings,
            $this->siteSettings,
            $this->userSettings,
            $this->acl,
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

    public function testEveryCoreOptionHasACategory()
    {
        $config = require OMEKA_PATH . '/application/config/module.config.php';
        foreach ($config['option_sidebars'] as $key => $spec) {
            $plugins = $config[$key];
            $names = array_merge(array_keys($plugins['invokables'] ?? []), array_keys($plugins['factories'] ?? []));
            foreach (array_diff($names, $spec['exclude'] ?? []) as $name) {
                $this->assertArrayHasKey($name, $plugins['category_names'], "$key: $name");
                $this->assertArrayHasKey($plugins['category_names'][$name], $plugins['categories'], "$key: $name");
            }
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
                $this->settings,
                $this->siteSettings,
                $this->userSettings,
                $this->acl,
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
