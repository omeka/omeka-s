<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Command;

use InvalidArgumentException;
use Laminas\ServiceManager\Exception;
use Laminas\ServiceManager\Tool\ConfigDumperInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_keys;
use function assert;
use function class_exists;
use function dirname;
use function file_exists;
use function file_put_contents;
use function is_array;
use function is_string;
use function is_writable;
use function sprintf;

/**
 * @internal CLI commands are not meant to be used in any upstream projects other than via `laminas-cli`.
 */
final class ConfigDumperCommand extends Command
{
    public const NAME = 'servicemanager:generate-deps-for-config-factory';

    public function __construct(
        private readonly ConfigDumperInterface $configDumper,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->addOption(
            'ignore-unresolved',
            'i',
            InputOption::VALUE_NONE,
            'Ignore classes with unresolved direct dependencies.',
        );

        $this->addArgument(
            'configFile',
            InputArgument::REQUIRED,
            'Path to a config file for which to generate configuration. If the file does not exist, it will be created.'
            . ' If it does exist, it must return an array, and the file will be updated with new configuration.'
        );

        $this->addArgument(
            'class',
            InputArgument::REQUIRED,
            'Name of the class to reflect and for which to generate dependency configuration.'
        );

        $this->setDescription(
            'Reads the provided configuration file (creating it if it does not exist),'
            . ' and injects it with ConfigAbstractFactory dependency configuration for'
            . ' the provided class name, writing the changes back to the file.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configFile = $input->getArgument('configFile');
        assert(is_string($configFile));

        try {
            $configFromConfigFile = $this->getConfig($configFile);
        } catch (InvalidArgumentException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
            return self::FAILURE;
        }

        $class = $input->getArgument('class');
        assert(is_string($class));

        if (! class_exists($class)) {
            $output->writeln(sprintf(
                '<error>Class "%s" does not exist or could not be autoloaded.</error>',
                $class
            ));

            return self::FAILURE;
        }

        try {
            $config = $this->configDumper->createDependencyConfig(
                $configFromConfigFile,
                $class,
                $input->hasOption('ignore-unresolved')
            );
        } catch (Exception\InvalidArgumentException $exception) {
            $output->writeln(sprintf(
                '<error>Unable to create config for "%s": %s</error>',
                $class,
                $exception->getMessage()
            ));
            return self::FAILURE;
        }

        file_put_contents($configFile, $this->configDumper->dumpConfigFile($config));

        $output->writeln(sprintf(
            '<info>[DONE]</info> Changes written to %s',
            $configFile
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<string,mixed>
     */
    private function getConfig(string $configFile): array
    {
        if (file_exists($configFile)) {
            $config = require $configFile;

            $this->assertConfigurationIsMap($config, $configFile);

            return $config;
        }

        if (! is_writable(dirname($configFile))) {
            throw new InvalidArgumentException(sprintf(
                'Cannot create configuration at path "%s"; not writable.',
                $configFile
            ));
        }

        return [];
    }

    /**
     * @psalm-assert array<string,mixed> $config
     */
    private function assertConfigurationIsMap(mixed $config, string $configFile): void
    {
        if (! is_array($config)) {
            throw new InvalidArgumentException(sprintf(
                'Configuration at path "%s" does not return an array.',
                $configFile
            ));
        }

        if ($config === []) {
            return;
        }

        foreach (array_keys($config) as $key) {
            if (is_string($key)) {
                return;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Configuration at path "%s" does not return a map of configuration keys.',
            $configFile
        ));
    }
}
