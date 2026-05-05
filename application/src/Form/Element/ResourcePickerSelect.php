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
     * resources picker sidebar. Defaults to 'admin/default'.
     *
     * @return string
     */
    public function getResourcesEndpointRoute()
    {
        return $this->getOption('resources_endpoint_route') ?? 'admin/default';
    }

    /**
     * Get the route params for the resources picker sidebar controller action.
     * The 'action' param defaults to 'resource-picker' and can be overridden
     * via the 'resources_endpoint_route_params' option.
     *
     * @return array
     */
    public function getResourcesEndpointRouteParams()
    {
        $params = $this->getOption('resources_endpoint_route_params') ?? [];
        return array_merge(['action' => 'resource-picker'], $params);
    }

    /**
     * Get the view partial used to render each resource, both in the sidebar
     * list and as a selected item in the widget. Defaults to
     * 'omeka/admin/{controller}/resource-picker-resource', derived from the
     * 'controller' key in 'resources_endpoint_route_params'.
     *
     * @return string|null
     */
    public function getResourcePartial()
    {
        if ($partial = $this->getOption('resource_partial')) {
            return $partial;
        }
        $controller = $this->getResourcesEndpointRouteParams()['controller'] ?? null;
        return $controller ? sprintf('omeka/admin/%s/resource-picker-resource', $controller) : null;
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

    /**
     * Get the value type, which determines which resource identifier is
     * submitted with the form (e.g. 'id', 'term').
     *
     * @return string
     */
    public function getValueType()
    {
        return $this->getOption('value_type') ?? 'id';
    }
}
