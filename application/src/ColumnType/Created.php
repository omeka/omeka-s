<?php
namespace Omeka\ColumnType;

use Laminas\Form\Element as LaminasElement;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

class Created implements ColumnTypeInterface
{
    protected $formElements;

    public function __construct(ServiceLocatorInterface $formElements)
    {
        $this->formElements = $formElements;
    }

    public function getLabel() : string
    {
        return 'Created'; // @translate
    }

    public function getResourceTypes() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function getMaxColumns() : ?int
    {
        return 1;
    }

    public function prepareDataForm(PhpRenderer $view) : void
    {
    }

    public function renderDataForm(PhpRenderer $view, array $data) : string
    {
        return '';
    }

    public function getSortBy(array $data) : ?string
    {
        return 'created';
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        return $this->getlabel();
    }

    public function renderContent(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $data) : ?string
    {
        return $view->i18n()->dateFormat($resource->created());
    }
}
