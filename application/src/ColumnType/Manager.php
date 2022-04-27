<?php
namespace Omeka\ColumnType;

use Omeka\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = ColumnTypeInterface::class;

    public function get($name, $options = [], $usePeeringServiceManagers = true)
    {
        try {
            $instance = parent::get($name, $options, $usePeeringServiceManagers);
        } catch (ServiceNotFoundException $e) {
            $instance = new Unknown($name, $this->creationContext->get('FormElementManager'));
        }
        return $instance;
    }

    /**
     * Get column name=>label value options for use in a select element.
     *
     * @param string $resourceType
     * @return array
     */
    public function getValueOptions($resourceType)
    {
        $valueOptions = [];
        foreach ($this->getRegisteredNames() as $columnTypeName) {
            $columnType = $this->get($columnTypeName);
            if (in_array($resourceType, $columnType->getResourceTypes())) {
                $valueOptions[] = [
                    'value' => $columnTypeName,
                    'label' => $columnType->getLabel(),
                    'attributes' => [
                        'data-max-columns' => $columnType->getMaxColumns(),
                    ],
                ];
            }
        }
        return $valueOptions;
    }
}
