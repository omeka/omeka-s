<?php declare(strict_types=1);

namespace OmekaTest\Module;

use Omeka\Module\InfoReader;
use Omeka\Test\TestCase;

/**
 * Test InfoReader for modules and themes.
 *
 * Tests various combinations of:
 * - composer.json only
 * - module.ini/theme.ini only
 * - Both sources (composer.json takes precedence)
 * - Version extraction
 */
class InfoReaderTest extends TestCase
{
    protected $testPath;
    protected $infoReader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testPath = sys_get_temp_dir() . '/omeka_test_info_reader_' . uniqid();
        mkdir($this->testPath, 0755, true);
        mkdir($this->testPath . '/config', 0755, true);

        $this->infoReader = new InfoReader();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testPath);
        parent::tearDown();
    }

    protected function removeDirectory($path)
    {
        if (!is_dir($path)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($path);
    }

    // -------------------------------------------------------------------------
    // Tests: module.ini only
    // -------------------------------------------------------------------------

    public function testReadModuleIniOnly()
    {
        $ini = <<<'INI'
            [info]
            name = "Test Module"
            version = "1.2.3"
            author = "John Doe"
            INI;
        file_put_contents($this->testPath . '/config/module.ini', $ini);

        $info = $this->infoReader->read($this->testPath, 'module');

        $this->assertNotNull($info);
        $this->assertEquals('Test Module', $info['name']);
        $this->assertEquals('1.2.3', $info['version']);
        $this->assertEquals('John Doe', $info['author']);
    }

    public function testReadThemeIniOnly()
    {
        $ini = <<<'INI'
            [info]
            name = "Test Theme"
            version = "2.0.0"
            INI;
        file_put_contents($this->testPath . '/config/theme.ini', $ini);

        $info = $this->infoReader->read($this->testPath, 'theme');

        $this->assertNotNull($info);
        $this->assertEquals('Test Theme', $info['name']);
        $this->assertEquals('2.0.0', $info['version']);
    }

    public function testReadModuleIniWithAllFields()
    {
        $ini = <<<'INI'
            [info]
            name = "Complete Module"
            version = "3.0.0"
            author = "Jane Doe"
            description = "A complete test module"
            module_link = "https://example.com/module"
            author_link = "https://example.com/author"
            support_link = "https://example.com/support"
            omeka_version_constraint = "^4.0"
            configurable = true
            INI;
        file_put_contents($this->testPath . '/config/module.ini', $ini);

        $info = $this->infoReader->read($this->testPath, 'module');

        $this->assertNotNull($info);
        $this->assertEquals('Complete Module', $info['name']);
        $this->assertEquals('3.0.0', $info['version']);
        $this->assertEquals('Jane Doe', $info['author']);
        $this->assertEquals('A complete test module', $info['description']);
        $this->assertEquals('https://example.com/module', $info['module_link']);
        $this->assertEquals('https://example.com/author', $info['author_link']);
        $this->assertEquals('https://example.com/support', $info['support_link']);
        $this->assertEquals('^4.0', $info['omeka_version_constraint']);
        $this->assertTrue($info['configurable']);
    }

    // -------------------------------------------------------------------------
    // Tests: composer.json only
    // -------------------------------------------------------------------------

    public function testReadComposerJsonOnly()
    {
        $composer = [
            'name' => 'vendor/omeka-s-module-test-module',
            'description' => 'A test module',
            'version' => '1.0.0',
            'license' => 'GPL-3.0-or-later',
            'homepage' => 'https://example.com',
            'extra' => [
                'label' => 'Test Module Composer',
            ],
        ];
        file_put_contents($this->testPath . '/composer.json', json_encode($composer));

        $info = $this->infoReader->read($this->testPath, 'module');

        $this->assertNotNull($info);
        $this->assertEquals('Test Module Composer', $info['name']);
        $this->assertEquals('1.0.0', $info['version']);
        $this->assertEquals('A test module', $info['description']);
        $this->assertEquals('GPL-3.0-or-later', $info['license']);
        $this->assertEquals('https://example.com', $info['module_link']);
    }

    public function testReadComposerJsonWithAuthors()
    {
        $composer = [
            'name' => 'vendor/omeka-s-module-test',
            'extra' => [
                'label' => 'Test',
            ],
            'authors' => [
                [
                    'name' => 'John Smith',
                    'homepage' => 'https://johnsmith.com',
                ],
                [
                    'name' => 'Jane Smith',
                ],
            ],
        ];
        file_put_contents($this->testPath . '/composer.json', json_encode($composer));

        $info = $this->infoReader->read($this->testPath, 'module');

        // Only first author is used.
        $this->assertEquals('John Smith', $info['author']);
        $this->assertEquals('https://johnsmith.com', $info['author_link']);
    }

    public function testReadComposerJsonWithSupport()
    {
        $composer = [
            'name' => 'vendor/omeka-s-module-test',
            'extra' => [
                'label' => 'Test',
            ],
            'support' => [
                'issues' => 'https://github.com/vendor/test/issues',
            ],
        ];
        file_put_contents($this->testPath . '/composer.json', json_encode($composer));

        $info = $this->infoReader->read($this->testPath, 'module');

        $this->assertEquals('https://github.com/vendor/test/issues', $info['support_link']);
    }

    public function testReadComposerJsonWithKeywords()
    {
        $composer = [
            'name' => 'vendor/omeka-s-module-test',
            'extra' => [
                'label' => 'Test',
            ],
            'keywords' => [
                'omeka s',
                'module',
                'search',
                'filter',
            ],
        ];
        file_put_contents($this->testPath . '/composer.json', json_encode($composer));

        $info = $this->infoReader->read($this->testPath, 'module');

        // Generic keywords should be filtered out.
        $this->assertEquals('search, filter', $info['tags']);
    }

    public function testReadComposerJsonForTheme()
    {
        $composer = [
            'name' => 'vendor/omeka-s-theme-test-theme',
            'homepage' => 'https://example.com/theme',
            'extra' => [
                'label' => 'Test Theme',
            ],
        ];
        file_put_contents($this->testPath . '/composer.json', json_encode($composer));

        $info = $this->infoReader->read($this->testPath, 'theme');

        $this->assertNotNull($info);
        $this->assertEquals('Test Theme', $info['name']);
        $this->assertEquals('https://example.com/theme', $info['theme_link']);
    }

    public function testReadComposerJsonWithConfigurable()
    {
        $composer = [
            'name' => 'vendor/omeka-s-module-test',
            'extra' => [
                'label' => 'Test',
                'configurable' => true,
            ],
        ];
        file_put_contents($this->testPath . '/composer.json', json_encode($composer));

        $info = $this->infoReader->read($this->testPath, 'module');

        $this->assertTrue($info['configurable']);
    }

    // -------------------------------------------------------------------------
    // Tests: Both sources (composer.json takes precedence)
    // -------------------------------------------------------------------------

    public function testComposerJsonTakesPrecedenceOverIni()
    {
        // Create module.ini with some values.
        $ini = <<<'INI'
            [info]
            name = "Module Ini Name"
            version = "1.0.0"
            description = "Description from ini"
            INI;
        file_put_contents($this->testPath . '/config/module.ini', $ini);

        // Create composer.json with different values.
        $composer = [
            'name' => 'vendor/omeka-s-module-test',
            'version' => '2.0.0',
            'description' => 'Description from composer',
            'extra' => [
                'label' => 'Module Composer Name',
            ],
        ];
        file_put_contents($this->testPath . '/composer.json', json_encode($composer));

        $info = $this->infoReader->read($this->testPath, 'module');

        // Composer values should take precedence.
        $this->assertEquals('Module Composer Name', $info['name']);
        $this->assertEquals('2.0.0', $info['version']);
        $this->assertEquals('Description from composer', $info['description']);
    }

    public function testIniValuesUsedWhenNotInComposer()
    {
        // Create module.ini with author.
        $ini = <<<'INI'
            [info]
            name = "Module"
            version = "1.0.0"
            author = "Ini Author"
            support_link = "https://ini-support.com"
            INI;
        file_put_contents($this->testPath . '/config/module.ini', $ini);

        // Create composer.json without author/support.
        $composer = [
            'name' => 'vendor/omeka-s-module-test',
            'extra' => [
                'label' => 'Module',
            ],
        ];
        file_put_contents($this->testPath . '/composer.json', json_encode($composer));

        $info = $this->infoReader->read($this->testPath, 'module');

        // Values not in composer should come from ini.
        $this->assertEquals('Ini Author', $info['author']);
        $this->assertEquals('https://ini-support.com', $info['support_link']);
    }

    // -------------------------------------------------------------------------
    // Tests: Defaults and fallbacks
    // -------------------------------------------------------------------------

    public function testDefaultNameFromDirectoryName()
    {
        // No label in extra, so should derive from directory.
        $composer = [
            'name' => 'vendor/test',
        ];
        file_put_contents($this->testPath . '/composer.json', json_encode($composer));

        $info = $this->infoReader->read($this->testPath, 'module');

        // Name should be derived from directory or project name.
        $this->assertNotEmpty($info['name']);
    }

    public function testDefaultVersionWhenMissing()
    {
        // No version specified.
        $ini = <<<'INI'
            [info]
            name = "Test Module"
            INI;
        file_put_contents($this->testPath . '/config/module.ini', $ini);

        $info = $this->infoReader->read($this->testPath, 'module');

        // Default version should be applied.
        $this->assertEquals('1.0.0', $info['version']);
    }

    public function testDefaultConfigurableIsFalse()
    {
        $ini = <<<'INI'
            [info]
            name = "Test Module"
            version = "1.0.0"
            INI;
        file_put_contents($this->testPath . '/config/module.ini', $ini);

        $info = $this->infoReader->read($this->testPath, 'module');

        $this->assertFalse($info['configurable']);
    }

    // -------------------------------------------------------------------------
    // Tests: Invalid or missing sources
    // -------------------------------------------------------------------------

    public function testReadReturnsNullWhenNoSources()
    {
        // No composer.json and no module.ini.
        $info = $this->infoReader->read($this->testPath, 'module');

        $this->assertNull($info);
    }

    public function testIsValidReturnsFalseForNull()
    {
        $this->assertFalse($this->infoReader->isValid(null));
    }

    public function testIsValidReturnsFalseForEmptyArray()
    {
        $this->assertFalse($this->infoReader->isValid([]));
    }

    public function testIsValidReturnsFalseWithoutName()
    {
        $this->assertFalse($this->infoReader->isValid(['version' => '1.0.0']));
    }

    public function testIsValidReturnsTrueWithName()
    {
        $this->assertTrue($this->infoReader->isValid(['name' => 'Test']));
    }

    public function testReadInvalidIniFile()
    {
        // Create a malformed ini file.
        file_put_contents($this->testPath . '/config/module.ini', 'invalid [[ content');

        $info = $this->infoReader->read($this->testPath, 'module');

        // Should return null when ini is invalid and no composer.json.
        $this->assertNull($info);
    }

    public function testReadInvalidComposerJson()
    {
        // Create a malformed JSON file.
        file_put_contents($this->testPath . '/composer.json', '{invalid json');

        $info = $this->infoReader->read($this->testPath, 'module');

        // Should return null when JSON is invalid and no ini.
        $this->assertNull($info);
    }

    // -------------------------------------------------------------------------
    // Tests: Project name conversions
    // -------------------------------------------------------------------------

    public function testProjectNameToLabel()
    {
        $reader = new InfoReader();

        $this->assertEquals(
            'Easy Admin',
            $reader->projectNameToLabel('daniel-km/omeka-s-module-easy-admin')
        );
        $this->assertEquals(
            'Advanced Search',
            $reader->projectNameToLabel('biblibre/omeka-s-module-advanced-search')
        );
        $this->assertEquals(
            'Foundation S',
            $reader->projectNameToLabel('omeka-s-themes/foundation-s')
        );
        $this->assertEquals(
            'Flavor',
            $reader->projectNameToLabel('vendor/omeka-s-theme-flavor')
        );
        // Suffixes are removed like AddonInstaller::inflect*.
        $this->assertEquals(
            'Datascribe',
            $reader->projectNameToLabel('chnm/Datascribe-module')
        );
        $this->assertEquals(
            'Neatline',
            $reader->projectNameToLabel('vendor/neatline-omeka-s')
        );
    }

    public function testProjectNameToDirectory()
    {
        $reader = new InfoReader();

        $this->assertEquals(
            'EasyAdmin',
            $reader->projectNameToDirectory('daniel-km/omeka-s-module-easy-admin')
        );
        $this->assertEquals(
            'AdvancedSearch',
            $reader->projectNameToDirectory('biblibre/omeka-s-module-advanced-search')
        );
        $this->assertEquals(
            'FoundationS',
            $reader->projectNameToDirectory('omeka-s-themes/foundation-s')
        );
        // Suffixes are removed like AddonInstaller::inflect*.
        $this->assertEquals(
            'Datascribe',
            $reader->projectNameToDirectory('chnm/Datascribe-module')
        );
        $this->assertEquals(
            'Neatline',
            $reader->projectNameToDirectory('vendor/neatline-omeka-s')
        );
    }

    // -------------------------------------------------------------------------
    // Tests: Installer name from composer.json
    // -------------------------------------------------------------------------

    public function testGetInstallerNameFromExtra()
    {
        $composer = [
            'name' => 'vendor/omeka-s-module-test',
            'extra' => [
                'installer-name' => 'CustomName',
            ],
        ];
        file_put_contents($this->testPath . '/composer.json', json_encode($composer));

        $name = $this->infoReader->getInstallerName($this->testPath);

        $this->assertEquals('CustomName', $name);
    }

    public function testGetInstallerNameFromProjectName()
    {
        $composer = [
            'name' => 'vendor/omeka-s-module-easy-admin',
        ];
        file_put_contents($this->testPath . '/composer.json', json_encode($composer));

        $name = $this->infoReader->getInstallerName($this->testPath);

        $this->assertEquals('EasyAdmin', $name);
    }

    public function testGetInstallerNameReturnsNullWithoutComposer()
    {
        $name = $this->infoReader->getInstallerName($this->testPath);

        $this->assertNull($name);
    }
}
