<?php
namespace Omeka\DataType;

use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Fallback implements DataTypeInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var string The name of the unknown data type
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return sprintf('%s [%s]', $translator->translate('Unknown'), $this->name);
    }
}
