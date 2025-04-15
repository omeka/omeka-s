<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Command;

use Laminas\ServiceManager\Exception;
use Laminas\ServiceManager\Tool\FactoryCreatorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function assert;
use function class_exists;
use function is_string;
use function sprintf;

/**
 * @internal CLI commands are not meant to be used in any upstream projects other than via `laminas-cli`.
 */
final class FactoryCreatorCommand extends Command
{
    public const NAME = 'servicemanager:generate-factory-for-class';

    public function __construct(
        private readonly FactoryCreatorInterface $factoryCreator,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->addArgument(
            'className',
            InputArgument::REQUIRED,
            'Name of the class to reflect and for which to generate a factory.'
        );
        $this->setDescription(
            'Generates to STDOUT a factory for creating the specified class; this may then'
            . ' be added to your application, and configured as a factory for the class.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $className = $input->getArgument('className');
        assert(is_string($className));

        if (! class_exists($className)) {
            $output->writeln(sprintf(
                '<error>Class "%s" does not exist or could not be autoloaded.</error>',
                $className
            ));
            return self::FAILURE;
        }

        try {
            $factory = $this->factoryCreator->createFactory($className);
        } catch (Exception\InvalidArgumentException $e) {
            $output->writeln(sprintf(
                '<error>Unable to create factory for "%s": %s</error>',
                $className,
                $e->getMessage()
            ));
            return self::FAILURE;
        }

        $output->writeln($factory);
        return self::SUCCESS;
    }
}
