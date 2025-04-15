<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Test;

use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\AbstractSingleInstancePluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use stdClass;

use function assert;
use function is_string;

/**
 * Trait for testing plugin managers for compatibility
 *
 * To use this trait:
 *   * implement the `getPluginManager()` method to return your plugin manager
 *
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 */
trait CommonPluginManagerTrait
{
    public function testInstanceOfMatches(): void
    {
        $manager    = self::getPluginManager();
        $reflection = new ReflectionProperty($manager, 'instanceOf');
        $this->assertEquals($this->getInstanceOf(), $reflection->getValue($manager), 'instanceOf does not match');
    }

    public function testRegisteringInvalidElementRaisesException(): void
    {
        $this->expectException($this->getServiceNotFoundException());
        self::getPluginManager()->setService('test', $this);
    }

    public function testLoadingInvalidElementRaisesException(): void
    {
        $manager = self::getPluginManager();
        $manager->setInvokableClass('test', stdClass::class);
        $this->expectException($this->getServiceNotFoundException());
        $manager->get('test');
    }

    #[DataProvider('aliasProvider')]
    public function testPluginAliasesResolve(string $alias, string $expected): void
    {
        $this->assertInstanceOf($expected, self::getPluginManager()->get($alias), "Alias '$alias' does not resolve'");
    }

    /**
     * @return list<array{string,string}>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function aliasProvider(): array
    {
        $manager                 = self::getPluginManager();
        $pluginContainerProperty = new ReflectionProperty(AbstractPluginManager::class, 'plugins');
        $pluginContainer         = $pluginContainerProperty->getValue($manager);
        self::assertInstanceOf(ServiceManager::class, $pluginContainer);

        $reflection = new ReflectionProperty($pluginContainer, 'aliases');
        $data       = [];
        foreach ($reflection->getValue($pluginContainer) as $alias => $expected) {
            assert(is_string($alias) && is_string($expected));
            $data[] = [$alias, $expected];
        }

        return $data;
    }

    protected function getServiceNotFoundException(): string
    {
        return InvalidServiceException::class;
    }

    /**
     * Returns the plugin manager to test
     *
     * @param ServiceManagerConfiguration $config
     */
    abstract protected static function getPluginManager(array $config = []): AbstractSingleInstancePluginManager;

    /**
     * Returns the value the instanceOf property has been set to
     *
     * @return class-string
     */
    abstract protected function getInstanceOf(): string;
}
