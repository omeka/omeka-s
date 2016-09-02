<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Stdlib\DateTime;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\HelperPluginManager;

/**
 * Abstract representation.
 *
 * Provides functionality for all representations.
 */
abstract class AbstractRepresentation implements RepresentationInterface
{
    use EventManagerAwareTrait;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var HelperPluginManager
     */
    protected $viewHelperManager;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Get an adapter by resource name.
     *
     * @param string $resourceName
     * @return AdapterInterface
     */
    protected function getAdapter($resourceName)
    {
        return $this->getServiceLocator()
            ->get('Omeka\ApiAdapterManager')
            ->get($resourceName);
    }

    /**
     * Get a JSON serializable instance of DateTime.
     *
     * @param \DateTime $dateTime
     * @return DateTime
     */
    protected function getDateTime(\DateTime $dateTime)
    {
        return new DateTime($dateTime);
    }

    /**
     * Get the translator service
     *
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->getServiceLocator()->get('MvcTranslator');
        }
        return $this->translator;
    }

    /**
     * Get a view helper from the manager.
     *
     * @param string $name
     * @return TranslatorInterface
     */
    protected function getViewHelper($name)
    {
        if (!$this->viewHelperManager instanceof HelperPluginManager) {
            $this->viewHelperManager = $this->getServiceLocator()
                ->get('ViewHelperManager');
        }
        return $this->viewHelperManager->get($name);
    }

    /**
     * Get one Media representation that typifies this representation.
     *
     * @return null|MediaRepresentation
     */
    public function primaryMedia()
    {
        return null;
    }

    /**
     * Get the service locator.
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->services;
    }

    /**
     * Set the service locator.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
        $this->setEventManager($serviceLocator->get('EventManager'));
    }
}
