<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool;

use Brick\VarExporter\VarExporter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\Tool\ConstructorParameterResolver\ConstructorParameterResolverInterface;
use Laminas\ServiceManager\Tool\ConstructorParameterResolver\ServiceFromContainerConstructorParameter;
use Psr\Container\ContainerInterface;

use function array_map;
use function array_shift;
use function assert;
use function class_exists;
use function count;
use function implode;
use function is_string;
use function preg_replace;
use function sort;
use function sprintf;
use function str_contains;
use function str_repeat;
use function strrpos;
use function substr;

use const PHP_EOL;

/**
 * @internal
 */
final class FactoryCreator implements FactoryCreatorInterface
{
    private const NAMESPACE_SEPARATOR = '\\';

    // phpcs:disable Generic.Files.LineLength
    private const FACTORY_TEMPLATE = <<<'EOT'
        <?php

        declare(strict_types=1);
        %s
        %s

        class %sFactory implements FactoryInterface
        {
            public function __invoke(ContainerInterface $container, string $requestedName, array|null $options = null): %s
            {
                return new %s(%s);
            }
        }

        EOT;
    // phpcs:enable Generic.Files.LineLength

    private const IMPORT_ALWAYS = [
        FactoryInterface::class,
        ContainerInterface::class,
    ];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConstructorParameterResolverInterface $constructorParameterResolver
    ) {
    }

    public function createFactory(string $className, array $aliases = []): string
    {
        $class     = $this->getClassName($className);
        $namespace = $this->getNamespace($className, $class);

        return sprintf(
            self::FACTORY_TEMPLATE,
            $namespace,
            $this->createImportStatements(),
            $class,
            $class,
            $class,
            $this->createArgumentString($className, $aliases)
        );
    }

    /**
     * @param class-string $className
     * @return non-empty-string
     */
    private function getClassName(string $className): string
    {
        $lastNamespaceSeparator = strrpos($className, self::NAMESPACE_SEPARATOR);
        if ($lastNamespaceSeparator === false) {
            return $className;
        }

        $className = substr($className, $lastNamespaceSeparator + 1);
        assert($className !== '');

        return $className;
    }

    /**
     * @param class-string $className
     * @param array<string,string> $aliases
     * @return array<string>
     */
    private function getConstructorParameters(string $className, array $aliases): array
    {
        $dependencies = $this->constructorParameterResolver->resolveConstructorParameterServiceNamesOrFallbackTypes(
            $className,
            $this->container,
            $aliases,
        );

        $stringifiedConstructorArguments = [];

        foreach ($dependencies as $dependency) {
            if ($dependency instanceof ServiceFromContainerConstructorParameter) {
                $stringifiedConstructorArguments[] = sprintf(
                    '$container->get(%s)',
                    $this->export($dependency->serviceName)
                );
                continue;
            }

            $stringifiedConstructorArguments[] = $this->export($dependency->argumentValue);
        }

        return $stringifiedConstructorArguments;
    }

    /**
     * @param class-string $className
     * @param array<string,string> $aliases
     */
    private function createArgumentString(string $className, array $aliases): string
    {
        $arguments = array_map(
            static fn(string $dependency): string
            => sprintf('%s', $dependency),
            $this->getConstructorParameters($className, $aliases)
        );

        switch (count($arguments)) {
            case 0:
                return '';
            case 1:
                return array_shift($arguments);
            default:
                $argumentPad = str_repeat(' ', 12);
                $closePad    = str_repeat(' ', 8);
                return sprintf(
                    "\n%s%s\n%s",
                    $argumentPad,
                    implode(",\n" . $argumentPad, $arguments),
                    $closePad
                );
        }
    }

    private function createImportStatements(): string
    {
        $imports = self::IMPORT_ALWAYS;
        sort($imports);
        return implode("\n", array_map(static fn(string $import): string => sprintf('use %s;', $import), $imports));
    }

    private function export(mixed $value): string
    {
        if (is_string($value) && class_exists($value)) {
            return sprintf('\\%s::class', $value);
        }

        return VarExporter::export(
            $value,
            VarExporter::NO_CLOSURES | VarExporter::NO_SERIALIZE | VarExporter::NO_SERIALIZE | VarExporter::NO_SET_STATE
        );
    }

    /**
     * @param class-string $className
     * @param non-empty-string $class
     */
    private function getNamespace(string $className, string $class): string
    {
        if (! str_contains($className, self::NAMESPACE_SEPARATOR)) {
            return '';
        }

        return sprintf(
            '%snamespace %s;%s',
            PHP_EOL,
            preg_replace('/\\\\' . $class . '$/', '', $className),
            PHP_EOL
        );
    }
}
