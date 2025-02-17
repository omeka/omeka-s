<?php declare(strict_types=1);

namespace Common\View\Helper;

use Laminas\Form\FormElementManager;
use Omeka\DataType\Manager as DataTypeManager;

/**
 * View helper for rendering data types.
 */
class DataType extends \Omeka\View\Helper\DataType
{
    /**
     * @param FormElementManager
     */
    protected $formElementManager;

    public function __construct(DataTypeManager $dataTypeManager, array $valueAnnotatingDataTypes, FormElementManager $formElementManager)
    {
        $this->manager = $dataTypeManager;
        $this->valueAnnotatingDataTypes = $valueAnnotatingDataTypes;
        $this->dataTypes = $this->manager->getRegisteredNames();
        $this->formElementManager = $formElementManager;
    }

    /**
     * Override the core view helper in order to use the form element DataTypeSelect.
     *
     * {@inheritDoc}
     * @see \Omeka\View\Helper\DataType::getSelect()
     */
    public function getSelect($name, $value = null, $attributes = [])
    {
        $element = $this->formElementManager->get(\Common\Form\Element\DataTypeSelect::class);
        $element
            ->setName($name)
            ->setEmptyOption('Default')
            ->setAttributes($attributes);
        if (!$element->getAttribute('multiple') && is_array($value)) {
            $value = reset($value);
        }
        $element->setValue($value);
        // Fix an issue with chosen select.
        if (isset($attributes['class']) && strpos($attributes['class'], 'chosen-select') !== false) {
            $element->setEmptyOption('');
        }
        return $this->getView()->formSelect($element);
    }
}
