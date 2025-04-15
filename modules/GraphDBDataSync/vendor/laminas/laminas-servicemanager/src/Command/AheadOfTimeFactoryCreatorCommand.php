<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Command;

use Brick\VarExporter\VarExporter;
use Laminas\ServiceManager\ConfigProvider;
use Laminas\ServiceManager\Exception\RuntimeException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\Tool\AheadOfTimeFactoryCompiler\AheadOfTimeFactoryCompilerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function assert;
use function class_exists;
use function count;
use function dirname;
use function file_put_contents;
use function is_dir;
use function is_string;
use function is_writable;
use function mkdir;
use function preg_replace;
use function sprintf;
use function str_replace;

/**
 * @internal CLI commands are not meant to be used in any upstream projects other than via `laminas-cli`.
 *
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 */
final class AheadOfTimeFactoryCreatorCommand extends Command
{
    public const NAME = 'servicemanager:generate-aot-factories';

    public function __construct(
        private readonly array $config,
        private readonly string $factoryTargetPath,
        private readonly AheadOfTimeFactoryCompilerInterface $factoryCompiler,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Creates factories which replace the runtime overhead for `ReflectionBasedAbstractFactory`.'
        );
        $this->addArgument(
            'localConfigFilename',
            InputArgument::OPTIONAL,
            'Should be a path targeting a filename which will be created so that the config autoloading'
            . ' will pick it up. Using a `.local.php` suffix should verify that the file is overriding existing'
            . ' configuration.',
            'config/autoload/generated-factories.local.php',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->factoryTargetPath === '' || ! is_writable($this->factoryTargetPath)) {
            $output->writeln(sprintf(
                '<error>Please configure the `%s` configuration key in your projects config and ensure that the'
                . ' directory is registered to the composer autoloader using `classmap` and writable by the executing'
                . ' user. In case you are targeting a nonexistent directory, please create the appropriate directory'
                . ' structure before executing this command.</error>',
                ConfigProvider::CONFIGURATION_KEY_FACTORY_TARGET_PATH,
            ));

            return self::FAILURE;
        }

        $localConfigFilename = $input->getArgument('localConfigFilename');
        assert(is_string($localConfigFilename));

        if (! is_writable(dirname($localConfigFilename))) {
            $output->writeln(sprintf(
                '<error>Provided `localConfigFilename` argument "%s" is not writable. In case you are targeting a'
                . ' nonexistent directory, please create the appropriate directory structure before executing this'
                . ' command.</error>',
                $localConfigFilename,
            ));

            return self::FAILURE;
        }

        $compiledFactories = $this->factoryCompiler->compile($this->config);
        if ($compiledFactories === []) {
            $output->writeln(
                '<comment>There is no (more) service registered to use the `ReflectionBasedAbstractFactory`.</comment>'
            );

            return self::SUCCESS;
        }

        $containerConfigurations = [];

        foreach ($compiledFactories as $factory) {
            $dirName = preg_replace('/\W/', '', $factory->containerConfigurationKey);
            assert(is_string($dirName));

            $targetDirectory = sprintf(
                '%s/%s',
                $this->factoryTargetPath,
                $dirName,
            );

            $factoryClassName = sprintf('%sFactory', $factory->fullyQualifiedClassName);
            if (class_exists($factoryClassName)) {
                $output->writeln(sprintf(
                    '<error>There is already an existing factory class registered for "%s": %s</error>',
                    $factory->fullyQualifiedClassName,
                    $factoryClassName,
                ));

                return self::FAILURE;
            }

            if (! is_dir($targetDirectory)) {
                if (! mkdir($targetDirectory, recursive: true) && ! is_dir($targetDirectory)) {
                    throw new RuntimeException(sprintf('Unable to create directory "%s".', $targetDirectory));
                }
            }

            $factoryFileName = sprintf(
                '%s/%s.php',
                $targetDirectory,
                str_replace('\\', '_', $factoryClassName)
            );
            file_put_contents($factoryFileName, $factory->generatedFactory);
            if (! isset($containerConfigurations[$factory->containerConfigurationKey])) {
                $containerConfigurations[$factory->containerConfigurationKey] = ['factories' => []];
            }

            // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.NoAssignment
            /**
             * Psalm has to understand that the `factoryClassName` variable contains a class-string to a factory which
             * will be available once persisted to the filesystem and loaded via composer autoloading.
             *
             * Sadly, we do have to do this as psalm is not able to infer concatenated arrays.
             *
             * @var class-string<FactoryInterface> $factoryClassName
             */
            $containerConfigurations[$factory->containerConfigurationKey]['factories'] += [
                $factory->fullyQualifiedClassName => $factoryClassName,
            ];
        }

        file_put_contents($localConfigFilename, $this->createLocalAotContainerConfigContent($containerConfigurations));

        $output->writeln(sprintf('<info>Successfully created %d factories.</info>', count($compiledFactories)));
        return self::SUCCESS;
    }

    /**
     * @param non-empty-array<non-empty-string,ServiceManagerConfiguration> $containerConfigurations
     * @return non-empty-string
     */
    private function createLocalAotContainerConfigContent(array $containerConfigurations): string
    {
        return sprintf('<?php %s', VarExporter::export(
            $containerConfigurations,
            VarExporter::ADD_RETURN | VarExporter::CLOSURE_SNAPSHOT_USES
        ));
    }
}
