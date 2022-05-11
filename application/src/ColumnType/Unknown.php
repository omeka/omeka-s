<?php
namespace Omeka\ColumnType;

use Laminas\Form\Element as LaminasElement;
use Laminas\Form\FormElementManager;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractEntityRepresentation;

class Unknown implements ColumnTypeInterface
{
    protected string $name;
    protected FormElementManager $formElements;

    public function __construct(string $name, FormElementManager $formElements)
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

    public function renderDataForm(PhpRenderer $view, array $data) : string
    {
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
        return $view->formRow($dataElement);
    }

    public function getSortBy(array $data) : ?string
    {
        return null;
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        return $this->getLabel();
    }

    public function renderContent(PhpRenderer $view, AbstractEntityRepresentation $resource, array $data) : ?string
    {
        return '';
    }
}
