<?php
namespace Omeka\DataType;

use Omeka\Entity\Value;
use Omeka\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = DataTypeInterface::class;

    /**
     * @param Value $value
     * @return DataTypeInterface
     * @todo Quickly check on registered data type names?
     */
    public function getForExtract(Value $value)
    {
        $dataData = $value->getData();

        if (empty($dataData)) {
            $dataType = $value->getType();
            // Manage standard cases first.
            if (in_array($dataType, ['literal', 'resource', 'uri'])) {
                return $this->get($dataType);
            }
            // Manage special values.
            if (is_string($value->getUri())) {
                return $this->get('uri');
            }
            if ($value->getValueResource()) {
                return $this->get('resource');
            }
            try {
                return $this->get($dataType);
            } catch (ServiceNotFoundException $e) {
            }
        } else {
            // Manage types added by modules.
            try {
                return $this->get($dataData);
            } catch (ServiceNotFoundException $e) {
            }
            if (is_string($value->getUri())) {
                return $this->get('uri');
            }
            if ($value->getValueResource()) {
                return $this->get('resource');
            }
        }
        // Manage fallback.
        return $this->get('literal');
    }
}
