<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;
use Omeka\Api\Representation\EntityRepresentation;
use Omeka\Api\Representation\ResourceRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Abstract API adapter.
 */
abstract class AbstractAdapter implements AdapterInterface
{
    use EventManagerAwareTrait, ServiceLocatorAwareTrait;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritDoc}
     */
    public function search(Request $request)
    {
        throw new Exception\OperationNotImplementedException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the search operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function create(Request $request)
    {
        throw new Exception\OperationNotImplementedException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the create operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function batchCreate(Request $request)
    {
        throw new Exception\OperationNotImplementedException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the batch create operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function read(Request $request)
    {
        throw new Exception\OperationNotImplementedException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the read operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function update(Request $request)
    {
        throw new Exception\OperationNotImplementedException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the update operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Request $request)
    {
        throw new Exception\OperationNotImplementedException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the delete operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * Get an adapter by resource name.
     *
     * @param string $resourceName
     * @return AdapterInterface
     */
    public function getAdapter($resourceName)
    {
        return $this->getServiceLocator()
            ->get('Omeka\ApiAdapterManager')
            ->get($resourceName);
    }

    /**
     * Compose a resource representation object.
     *
     * @param string|int $id The unique identifier of the resource
     * @param mixed $data Whatever data is needed to compose the representation.
     * @return RepresentationInterface|null
     */
    public function getRepresentation($id, $data) {

        // Do not attempt to compose a null representation.
        if (null === $data) {
            return null;
        }

        if ($data instanceof EntityInterface) {
            $id = $data->getId();
        }

        $representationClass = $this->getRepresentationClass();
        return new $representationClass($id, $data, $this);
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
     * {@inheritDoc}
     */
    public function getResourceId()
    {
        return get_called_class();
    }
}
