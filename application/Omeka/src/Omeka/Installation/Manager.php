<?php
namespace Omeka\Installation;

use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Installation manager service.
 */
class Manager implements ServiceLocatorAwareInterface
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array Registered installation tasks.
     */
    protected $tasks = array();

    /**
     * @var array Registered task variables.
     */
    protected $vars = array();

    /**
     * Install Omeka.
     *
     * @return Result
     */
    public function install()
    {
        $result = new Result;
        $isInstalled = $this->getServiceLocator()
            ->get('Omeka\InstallationStatus')->isInstalled();

        if ($isInstalled) {
            $result->addMessage(
                $this->getTranslator()->translate('Omeka is already installed.'),
                Result::MESSAGE_TYPE_ERROR
            );
            return $result;
        }

        foreach ($this->getTasks() as $taskName) {
            $start = microtime(true);
            $task = new $taskName($this->getServiceLocator(), $result);

            // Set task-specific variables.
            $vars = $this->getVars($taskName);
            if ($vars) {
                $task->setVars($vars);
            }

            $task->perform();
            $end = microtime(true);
            $result->addMessage(sprintf('time: %.2f', $end - $start));

            // Tasks are dependent on previously run tasks. If there is an
            // error, stop installation immediately and return the result.
            if ($result->isError()) {
                return $result;
            }
        }
        return $result;
    }

    /**
     * Register an installation task.
     * 
     * @param string $task
     */
    public function registerTask($task)
    {
        $t = $this->getTranslator();
        if (!class_exists($task)) {
            throw new Exception\ConfigException(sprintf(
                $t->translate('The "%s" installation task does not exist.'),
                $task
            ));
        }
        if (!is_subclass_of($task, 'Omeka\Installation\Task\TaskInterface')) {
            throw new Exception\ConfigException(sprintf(
                $t->translate('The "%s" installation task does not implement Omeka\Installation\Task\TaskInterface.'),
                $task
            ));
        }
        $this->tasks[] = $task;
    }

    /**
     * Register installation tasks.
     * 
     * @param array $tasks
     */
    public function registerTasks(array $tasks)
    {
        foreach ($tasks as $task) {
            $this->registerTask($task);
        }
    }

    /**
     * Get registered installation tasks.
     * 
     * @return array
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * Register a specific task's variables.
     *
     * @param str $task
     * @param array $vars
     */
    public function registerVars($task, array $vars)
    {
        $this->vars[$task] = $vars;
    }

    /**
     * Get a specific task's variables.
     *
     * @return array|null
     */
    public function getVars($task)
    {
        return isset($this->vars[$task]) ? $this->vars[$task] : null;
    }

    /**
     * Get the translator service
     *
     * return TranslatorInterface
     */
    public function getTranslator()
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->getServiceLocator()->get('MvcTranslator');
        }
        return $this->translator;
    }

    /**
     * {@inheritdoc}
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}
