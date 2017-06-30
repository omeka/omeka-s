<?php
namespace Omeka\View\Helper;

use Omeka\DataType\Manager as DataTypeManager;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ResourceTemplateRepresentation;
use Zend\Form\Element\Select;
use Zend\View\Helper\AbstractHelper;

class DataType extends AbstractHelper
{
    /**
     * @var DataTypeManager
     */
    protected $manager;

    protected $dataTypes;

    public function __construct(DataTypeManager $dataTypeManager)
    {
        $this->manager = $dataTypeManager;
        $this->dataTypes = $this->manager->getRegisteredNames();
    }

    /**
     * Get the data type select markup.
     *
     * @param string $name
     * @param string $value
     */
    public function getSelect($name, $value, $attributes = [])
    {
        $valueOptions = [];
        foreach ($this->dataTypes as $dataTypeName) {
            $valueOptions[$dataTypeName] = $this->manager->get($dataTypeName)->getLabel();
        }

        $element = new Select($name);
        $element->setEmptyOption('Default')
            ->setValueOptions($valueOptions)
            ->setAttributes($attributes);
        if (!array_key_exists($value, $valueOptions)) {
            $value = null;
        }
        $element->setValue($value);
        return $this->getView()->formSelect($element);
    }

    public function getOptionsForms()
    {
        $optionsForms = [];
        foreach ($this->dataTypes as $dataTypeName) {
            $optionsForm = $this->manager->get($dataTypeName)->optionsForm($this->getView());
            if ($optionsForm) {
                $optionsForms[$dataTypeName] = $optionsForm;
            }
        }
        return $optionsForms;
    }

    /**
     * Get the template markup for all data types.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @param ResourceTemplateRepresentation $resourceTemplate
     * @return string
     */
    public function getTemplates(
        AbstractResourceEntityRepresentation $resource = null,
        ResourceTemplateRepresentation $resourceTemplate = null
    ) {
        $view = $this->getView();
        $templateVars = [];
        if ($resourceTemplate) {
            // Get markup defined by the passed resource template.
            foreach ($resourceTemplate->resourceTemplateProperties() as $resTemProp) {
                if ($resTemProp->dataType()) {
                    $templateVars[] = [
                        'resourceTemplateProperty' => $resTemProp,
                        'dataType' => $resTemProp->dataType(),
                    ];
                }
            }
        } else {
            // Get markup for the default data types.
            foreach (['literal', 'uri', 'resource'] as $dataType) {
                $templateVars[] = [
                    'resourceTemplateProperty' => null,
                    'dataType' => $dataType,
                ];
            }
        }
        $templates = [];
        foreach ($templateVars as $templateVar) {
            $templateVar['resource'] = $resource;
            $templateVar['resourceTemplate'] = $resourceTemplate;
            $templates[] = $view->partial('common/data-type-wrapper', $templateVar);
        }
        return implode(PHP_EOL, $templates);
    }

    /**
     * Get the template markup for a data type.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @param mixed $options
     */
    public function getTemplate(
        $dataType,
        AbstractResourceEntityRepresentation $resource = null,
        $options = null
    ) {
        return $this->manager->get($dataType)->form($this->getView(), $resource, $options);
    }

    /**
     * Prepare the view to enable the data type options.
     */
    public function prepareOptionsForm()
    {
        foreach ($this->dataTypes as $dataType) {
            $this->manager->get($dataType)->prepareOptionsForm($this->getView());
        }
    }

    /**
     * Prepare the view to enable the data types.
     */
    public function prepareForm()
    {
        foreach ($this->dataTypes as $dataType) {
            $this->manager->get($dataType)->prepareForm($this->getView());
        }
    }
}
