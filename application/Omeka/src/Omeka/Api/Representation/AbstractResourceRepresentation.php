<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;

/**
 * Abstract API resource representation.
 *
 * Provides functionality for representations of registered API resources.
 */
abstract class AbstractResourceRepresentation extends AbstractRepresentation
{
    /**
     * The Omeka application namespace IRI.
     */
    const OMEKA_IRI = 'http://omeka.org/vocabulary#';

    /**
     * @var string|int
     */
    protected $id;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var array The JSON-LD context.
     */
    protected $context = array(
        'o' => self::OMEKA_IRI,
    );

    /**
     * Construct the resource representation object.
     *
     * @param string|int $id The unique identifier of this resource
     * @param mixed $data The data from which to derive a representation
     * @param ServiceLocatorInterface $adapter The corresponsing adapter
     */
    public function __construct($id, $data, AdapterInterface $adapter)
    {
        // Set the service locator first.
        $this->setServiceLocator($adapter->getServiceLocator());
        $this->setId($id);
        $this->setData($data);
        $this->setAdapter($adapter);
    }

    /**
     * Get an array representation of this resource using JSON-LD notation.
     *
     * @return array
     */
    abstract public function getJsonLd();

    /**
     * Compose the complete JSON-LD object.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $jsonLd = $this->getJsonLd();
        return array_merge(
            array('@context' => $this->context),
            $jsonLd
        );
    }

    /**
     * Add a term definition to the JSON-LD context.
     *
     * @param string $term
     * @param string|array $map The IRI or an array defining the term
     */
    protected function addTermDefinitionToContext($term, $map)
    {
        $this->context[$term] = $map;
    }

    /**
     * Set the unique resource identifier.
     *
     * @param $id
     */
    protected function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the unique resource identifier.
     *
     * @return string|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the corresponding adapter.
     *
     * @param AdapterInterface $adapter
     */
    protected function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Get the corresponding adapter or another adapter by resource name.
     *
     * @param null|string $resourceName
     * @return AdapterInterface
     */
    protected function getAdapter($resourceName = null)
    {
        if (is_string($resourceName)) {
            return parent::getAdapter($resourceName);
        }
        return $this->adapter;
    }
}
