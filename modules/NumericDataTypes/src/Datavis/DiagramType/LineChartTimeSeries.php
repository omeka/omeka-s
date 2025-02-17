<?php
namespace NumericDataTypes\Datavis\DiagramType;

use Datavis\DiagramType\DiagramTypeInterface;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SiteRepresentation;

class LineChartTimeSeries implements DiagramTypeInterface
{
    public function getLabel() : string
    {
        return 'Line chart (time series)'; // @translate
    }

    public function addElements(SiteRepresentation $site, Fieldset $fieldset) : void
    {
        $defaults = [
            'plot_type' => 'line',
            'line_type' => 'linear',
            'width' => 700,
            'height' => 700,
            'margin_top' => 30,
            'margin_right' => 30,
            'margin_bottom' => 100,
            'margin_left' => 60,
        ];

        $fieldset->add([
            'type' => Element\Select::class,
            'name' => 'plot_type',
            'options' => [
                'label' => 'Plot type',
                'value_options' => [
                    'line' => 'Line (no points)', // @translate
                    'points' => 'Points (no line)', // @translate
                    'line_points' => 'Line and points', // @translate
                ],
            ],
            'attributes' => [
                'value' => $defaults['plot_type'],
                'required' => true,
            ],
        ]);
        $fieldset->add([
            'type' => Element\Select::class,
            'name' => 'line_type',
            'options' => [
                'label' => 'Line type', // @translate
                'value_options' => [
                    'linear' => 'Linear', // @translate
                    'monotonex' => 'MonotoneX', // @translate
                    'natural' => 'Natural', // @translate
                    'step' => 'Step', // @translate
                    'stepafter' => 'StepAfter', // @translate
                    'stepbefore' => 'StepBefore', // @translate
                ],
            ],
            'attributes' => [
                'value' => $defaults['line_type'],
                'required' => true,
            ],
        ]);
        $fieldset->add([
            'type' => Element\Number::class,
            'name' => 'width',
            'options' => [
                'label' => 'Width', // @translate
            ],
            'attributes' => [
                'min' => 0,
                'value' => $defaults['width'],
                'placeholder' => $defaults['width'],
                'required' => true,
            ],
        ]);
        $fieldset->add([
            'type' => Element\Number::class,
            'name' => 'height',
            'options' => [
                'label' => 'Height', // @translate
            ],
            'attributes' => [
                'min' => 0,
                'value' => $defaults['height'],
                'placeholder' => $defaults['height'],
                'required' => true,
            ],
        ]);
        $fieldset->add([
            'type' => Element\Number::class,
            'name' => 'margin_top',
            'options' => [
                'label' => 'Margin top', // @translate
            ],
            'attributes' => [
                'min' => 0,
                'value' => $defaults['margin_top'],
                'placeholder' => $defaults['margin_top'],
                'required' => true,
            ],
        ]);
        $fieldset->add([
            'type' => Element\Number::class,
            'name' => 'margin_right',
            'options' => [
                'label' => 'Margin right', // @translate
            ],
            'attributes' => [
                'min' => 0,
                'value' => $defaults['margin_right'],
                'placeholder' => $defaults['margin_right'],
                'required' => true,
            ],
        ]);
        $fieldset->add([
            'type' => Element\Number::class,
            'name' => 'margin_bottom',
            'options' => [
                'label' => 'Margin bottom', // @translate
            ],
            'attributes' => [
                'min' => 0,
                'value' => $defaults['margin_bottom'],
                'placeholder' => $defaults['margin_bottom'],
                'required' => true,
            ],
        ]);
        $fieldset->add([
            'type' => Element\Number::class,
            'name' => 'margin_left',
            'options' => [
                'label' => 'Margin left', // @translate
            ],
            'attributes' => [
                'min' => 0,
                'value' => $defaults['margin_left'],
                'placeholder' => $defaults['margin_left'],
                'required' => true,
            ],
        ]);
    }

    public function prepareRender(PhpRenderer $view) : void
    {
        $view->headScript()->appendFile('https://d3js.org/d3.v6.js');
        $view->headScript()->appendFile($view->assetUrl('js/diagram-render/line_chart_time_series.js', 'Datavis'));
        $view->headLink()->appendStylesheet($view->assetUrl('css/diagram-render/line_chart_time_series.css', 'Datavis'));
    }
}
