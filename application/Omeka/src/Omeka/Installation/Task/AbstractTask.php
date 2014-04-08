<?php
namespace Omeka\Installation\Task;

use Omeka\Installation\Result;
use Omeka\Stdlib\ErrorStore;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract installation task.
 */
abstract class AbstractTask implements TaskInterface
{
    /**
     * @var Result
     */
    protected $result;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array
     */
    protected $vars = array();

    /**
     * Construct the task.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param Result $result
     */
    public function __construct(ServiceLocatorInterface $serviceLocator, Result $result)
    {
        $this->setServiceLocator($serviceLocator);
        $this->setResult($result);
        $result->setCurrentTask(get_class($this), $this->getName());
    }

    /**
     * Add an error message.
     *
     * @param string $message
     */
    public function addError($message)
    {
        $this->result->addMessage($message, Result::MESSAGE_TYPE_ERROR);
    }

    /**
     * Add errors derived from an ErrorStore.
     *
     * @param ErrorStore $errorStore
     */
    public function addErrorStore(ErrorStore $errorStore)
    {
        foreach ($errorStore->getErrors() as $error) {
            foreach ($error as $message) {
                $this->addError($message);
            }
        }
    }

    /**
     * Add a warning message.
     *
     * @param string $message
     */
    public function addWarning($message)
    {
        $this->result->addMessage($message, Result::MESSAGE_TYPE_WARNING);
    }

    /**
     * Add an info message.
     *
     * @param string $message
     */
    public function addInfo($message)
    {
        $this->result->addMessage($message, Result::MESSAGE_TYPE_INFO);
    }

    /**
     * Set the result object.
     *
     * @param Result $result
     */
    public function setResult(Result $result)
    {
        $this->result = $result;
    }

    /**
     * Get the result object.
     *
     * @return Result
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set this task's variables.
     *
     * @param array $vars
     */
    public function setVars(array $vars)
    {
        $this->vars = $vars;
    }

    /**
     * Get a variable set to this task.
     *
     * @param string $key
     * @return mixed
     */
    public function getVar($key)
    {
        return isset($this->vars[$key]) ? $this->vars[$key] : null;
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
