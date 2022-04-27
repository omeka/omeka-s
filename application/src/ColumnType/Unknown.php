<?php
namespace Omeka\ColumnType;

use Laminas\Form\Element as LaminasElement;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

class Unknown implements ColumnTypeInterface
{
    protected $name;
    protected $formElements;

    public function __construct(string $name, ServiceLocatorInterface $formElements)
    {
        $this->name = $name;
        $this->formElements = $formElements;
    }

    public function getLabel() : string
    {
        return '[Unknown]'; // @translate
    }

    public function getResourceTypes() : array
    {
        return [];
    }

    public function getMaxColumns() : ?int
    {
        return null;
    }

    public function prepareDataForm(PhpRenderer $view) : void
    {
    }

    public function renderDataForm(PhpRenderer $view, array $data) : string
    {
        $typeElement = $this->formElements->get(LaminasElement\Text::class);
        $typeElement->setName('column_type_unknown');
        $typeElement->setOptions([
            'label' => 'Unknown column type', // @translate
        ]);
        $typeElement->setAttributes([
            'value' => $this->name,
            'disabled' => true,
        ]);

        $dataElement = $this->formElements->get(LaminasElement\Textarea::class);
        $dataElement->setName('column_data_unknown');
        $dataElement->setOptions([
            'label' => 'Unknown column data', // @translate
        ]);
        $dataElement->setAttributes([
            'value' => json_encode($data, JSON_PRETTY_PRINT),
            'style' => 'height: 300px;',
            'disabled' => true,
        ]);

        return sprintf(
            '%s%s',
            $view->formRow($typeElement),
            $view->formRow($dataElement),
        );
    }

    public function getSortBy(array $data) : ?string
    {
        return null;
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        return '';
    }

    public function renderContent(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $data) : ?string
    {
        return '';
    }
}
