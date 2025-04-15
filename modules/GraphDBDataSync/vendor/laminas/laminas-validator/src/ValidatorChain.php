<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Countable;
use IteratorAggregate;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\PriorityQueue;
use Traversable;

use function array_replace;
use function assert;
use function count;
use function is_bool;
use function rsort;

use const SORT_NUMERIC;

/**
 * @psalm-type QueueElement = array{instance: ValidatorInterface, breakChainOnFailure: bool}
 * @implements IteratorAggregate<array-key, QueueElement>
 */
final class ValidatorChain implements Countable, IteratorAggregate, ValidatorInterface
{
    /**
     * Default priority at which validators are added
     */
    public const DEFAULT_PRIORITY = 1;

    /**
     * Validator chain
     *
     * @var PriorityQueue<QueueElement, int>
     */
    private PriorityQueue $validators;

    /**
     * Array of validation failure messages
     *
     * @var array<string, string>
     */
    private array $messages = [];

    /**
     * Initialize validator chain
     */
    public function __construct(
        private ValidatorPluginManager|null $pluginManager = null
    ) {
        /** @var PriorityQueue<QueueElement, int> $queue */
        $queue            = new PriorityQueue();
        $this->validators = $queue;
    }

    /**
     * Return the count of attached validators
     */
    public function count(): int
    {
        return count($this->validators);
    }

    /**
     * Retrieve the Validator Plugin Manager used by this instance
     *
     * If you need an instance of the plugin manager, you should retrieve it from your DI container. This method is
     * only for internal use and is kept for compatibility with laminas-inputfilter.
     *
     * It is not subject to BC because it is marked as internal and the method may be removed in a minor release.
     *
     * @internal \Laminas
     */
    public function getPluginManager(): ValidatorPluginManager
    {
        if ($this->pluginManager === null) {
            $this->pluginManager = new ValidatorPluginManager(new ServiceManager());
        }

        return $this->pluginManager;
    }

    /**
     * Set plugin manager instance
     *
     * This method is retained for BC with laminas-inputfilter. It is internal and not subject to BC guarantees.
     * It may be removed in a minor release.
     *
     * @internal \Laminas
     */
    public function setPluginManager(ValidatorPluginManager $plugins): void
    {
        $this->pluginManager = $plugins;
    }

    /**
     * Retrieve a validator by name
     *
     * This method is retained for BC with laminas-inputfilter. It is internal and not subject to BC guarantees.
     * It may be removed in a minor release.
     *
     * @internal \Laminas
     *
     * @param string|class-string<T> $name    Name of validator to return
     * @param array<string, mixed>   $options Options to pass to validator constructor
     *                                        (if not already instantiated)
     * @template T of ValidatorInterface
     * @return ($name is class-string<T> ? T : ValidatorInterface)
     */
    public function plugin(string $name, array $options = []): ValidatorInterface
    {
        $plugin = $this->getPluginManager()->build($name, $options);
        assert($plugin instanceof ValidatorInterface);

        return $plugin;
    }

    /**
     * Attach a validator to the end of the chain
     * If $breakChainOnFailure is true, then if the validator fails, the next validator in the chain,
     * if one exists, will not be executed.
     *
     * @throws Exception\InvalidArgumentException
     */
    public function attach(
        ValidatorInterface $validator,
        bool $breakChainOnFailure = false,
        int $priority = self::DEFAULT_PRIORITY
    ): void {
        $this->validators->insert(
            [
                'instance'            => $validator,
                'breakChainOnFailure' => $breakChainOnFailure,
            ],
            $priority
        );
    }

    /**
     * Adds a validator to the beginning of the chain
     *
     * If $breakChainOnFailure is true, then if the validator fails, the next validator in the chain,
     * if one exists, will not be executed.
     */
    public function prependValidator(ValidatorInterface $validator, bool $breakChainOnFailure = false): void
    {
        $priority = self::DEFAULT_PRIORITY;

        if (! $this->validators->isEmpty()) {
            $extractedNodes = $this->validators->toArray(PriorityQueue::EXTR_PRIORITY);
            rsort($extractedNodes, SORT_NUMERIC);
            $priority = $extractedNodes[0] + 1;
        }

        $this->validators->insert(
            [
                'instance'            => $validator,
                'breakChainOnFailure' => $breakChainOnFailure,
            ],
            $priority
        );
    }

    /**
     * Use the plugin manager to add a validator by name
     *
     * @param string|class-string<ValidatorInterface> $name
     * @param array<string, mixed> $options
     */
    public function attachByName(
        string $name,
        array $options = [],
        bool $breakChainOnFailure = false,
        int $priority = self::DEFAULT_PRIORITY,
    ): void {
        $bc = null;
        foreach (['break_chain_on_failure', 'breakchainonfailure'] as $key) {
            /** @psalm-var mixed $value */
            $value = $options[$key] ?? null;
            if (is_bool($value)) {
                $bc = $value;
            }
        }

        $bc ??= $breakChainOnFailure;

        $this->attach($this->plugin($name, $options), $bc, $priority);
    }

    /**
     * Use the plugin manager to prepend a validator by name
     *
     * @param string|class-string<ValidatorInterface> $name
     * @param array<string, mixed>                    $options
     */
    public function prependByName(string $name, array $options = [], bool $breakChainOnFailure = false): void
    {
        $this->prependValidator($this->plugin($name, $options), $breakChainOnFailure);
    }

    /**
     * Returns true if and only if $value passes all validations in the chain
     *
     * Validators are run in the order in which they were added to the chain (FIFO).
     *
     * @param array<string, mixed> $context Extra "context" to provide the validator
     */
    public function isValid(mixed $value, ?array $context = null): bool
    {
        $this->messages = [];
        $result         = true;
        foreach ($this as $element) {
            $validator = $element['instance'];
            assert($validator instanceof ValidatorInterface);
            if ($validator->isValid($value, $context)) {
                continue;
            }

            $result         = false;
            $this->messages = array_replace($this->messages, $validator->getMessages());
            if ($element['breakChainOnFailure']) {
                break;
            }
        }

        return $result;
    }

    /**
     * Merge the validator chain with the one given in parameter
     */
    public function merge(ValidatorChain $validatorChain): void
    {
        foreach ($validatorChain->validators->toArray(PriorityQueue::EXTR_BOTH) as $item) {
            $this->attach($item['data']['instance'], $item['data']['breakChainOnFailure'], $item['priority']);
        }
    }

    /**
     * Returns array of validation failure messages
     *
     * @return array<string, string>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Get all the validators
     *
     * @return list<QueueElement>
     */
    public function getValidators(): array
    {
        return $this->validators->toArray(PriorityQueue::EXTR_DATA);
    }

    /**
     * Invoke chain as command
     */
    public function __invoke(mixed $value): bool
    {
        return $this->isValid($value);
    }

    /**
     * Deep clone handling
     */
    public function __clone()
    {
        $this->validators = clone $this->validators;
    }

    /**
     * Prepare validator chain for serialization
     *
     * Plugin manager (property 'plugins') cannot
     * be serialized. On wakeup the property remains unset
     * and next invocation to getPluginManager() sets
     * the default plugin manager instance (ValidatorPluginManager).
     *
     * @return list<string>
     */
    public function __sleep(): array
    {
        return ['validators', 'messages'];
    }

    /** @return Traversable<array-key, QueueElement> */
    public function getIterator(): Traversable
    {
        return clone $this->validators;
    }
}
