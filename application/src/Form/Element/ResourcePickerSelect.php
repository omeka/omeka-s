<?php
namespace Omeka\Form\Element;

use Laminas\Form\Element;
use Laminas\InputFilter\InputProviderInterface;

class ResourcePickerSelect extends Element implements InputProviderInterface
{
    public function getInputSpecification()
    {
        return [
            'name' => $this->getName(),
            'required' => false,
        ];
    }

    /**
     * Get the Laminas route name for the controller action that serves the
     * resources picker sidebar.
     *
     * @return string|null
     */
    public function getResourcesEndpointRoute()
    {
        return $this->getOption('resources_endpoint_route');
    }

    /**
     * Get the route params for the resources picker sidebar controller action.
     *
     * @return array
     */
    public function getResourcesEndpointRouteParams()
    {
        return $this->getOption('resources_endpoint_route_params') ?? [];
    }

    /**
     * Get the view partial used to render each resource, both in the sidebar
     * list and as a selected item in the widget.
     *
     * @return string|null
     */
    public function getResourcePartial()
    {
        return $this->getOption('resource_partial');
    }

    /**
     * Get the API resource type used for server-side pre-population of
     * existing selections when the form is rendered.
     *
     * @return string|null
     */
    public function getApiResource()
    {
        return $this->getOption('api_resource');
    }

    /**
     * Get additional query params to merge into the endpoint URL. These are
     * forwarded to the API on initial load and preserved across sidebar
     * searches via hidden inputs.
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->getOption('query') ?? [];
    }

    /**
     * Whether this element allows multiple selections.
     *
     * @return bool
     */
    public function isMultiple()
    {
        return (bool) $this->getOption('multiple');
    }
}
