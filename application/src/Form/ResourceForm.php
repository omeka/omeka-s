<?php
namespace Omeka\Form;

use Omeka\Form\Element\ResourceSelect;

class ResourceForm extends AbstractForm
{
    public function buildForm()
    {
        $serviceLocator = $this->getServiceLocator();
        $url = $serviceLocator->get('ViewHelperManager')->get('url');

        $templateSelect = new ResourceSelect($serviceLocator);
        $templateSelect
            ->setName('o:resource_template[o:id]')
            ->setAttribute('id', 'resource-template-select')
            ->setAttribute('data-api-base-url', $url('api/default',
                ['resource' => 'resource_templates']))
            ->setLabel('Resource Template') // @translate
            ->setEmptyOption('Select Template') // @translate
            ->setOption('info', 'A pre-defined template for resource creation.') // @translate
            ->setResourceValueOptions(
                'resource_templates',
                [],
                function ($template, $serviceLocator) {
                    return $template->label();
                }
            );
        $this->add($templateSelect);

        $classSelect = new ResourceSelect($serviceLocator);
        $classSelect
            ->setName('o:resource_class[o:id]')
            ->setAttribute('id', 'resource-class-select')
            ->setLabel('Class') // @translate
            ->setEmptyOption('Select Class') // @translate
            ->setOption('info', 'A type for the resource. Different types have different default properties attached to them.') // @translate
            ->setResourceValueOptions(
                'resource_classes',
                [],
                function ($resourceClass, $serviceLocator) {
                    return [
                        $resourceClass->vocabulary()->label(),
                        $resourceClass->label()
                    ];
                }
            );
        $this->add($classSelect);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:resource_template[o:id]',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'o:resource_class[o:id]',
            'required' => false,
        ]);
    }
}
