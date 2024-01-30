<?php
namespace Omeka\Form\Element;

use Laminas\Form\Element as LaminasElement;
use Omeka\Form\Element as OmekaElement;

class Padding extends LaminasElement
{
    protected $topElement;
    protected $rightElement;
    protected $bottomElement;
    protected $leftElement;

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->topElement = (new OmekaElement\LengthCssDataType('padding_top'))
            ->setAttributes([
                'id' => 'block-layout-data-padding-top',
                'data-key' => 'padding_top',
            ]);
        $this->rightElement = (new OmekaElement\LengthCssDataType('padding_right'))
            ->setAttributes([
                'id' => 'block-layout-data-padding-right',
                'data-key' => 'padding_right',
            ]);
        $this->bottomElement = (new OmekaElement\LengthCssDataType('padding_bottom'))
            ->setAttributes([
                'id' => 'block-layout-data-padding-bottom',
                'data-key' => 'padding_bottom',
            ]);
        $this->leftElement = (new OmekaElement\LengthCssDataType('padding_left'))
            ->setAttributes([
                'id' => 'block-layout-data-padding-left',
                'data-key' => 'padding_left',
            ]);
    }

    public function getTopElement()
    {
        return $this->topElement;
    }

    public function getRightElement()
    {
        return $this->rightElement;
    }

    public function getBottomElement()
    {
        return $this->bottomElement;
    }

    public function getLeftElement()
    {
        return $this->leftElement;
    }
}
