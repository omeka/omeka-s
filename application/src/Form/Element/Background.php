<?php
namespace Omeka\Form\Element;

use Laminas\Form\Element as LaminasElement;
use NumericDataTypes\DataType\Integer as IntegerDataType;
use Omeka\Form\Element as OmekaElement;

class Background extends LaminasElement
{
    protected $imageAssetElement;
    protected $positionYElement;
    protected $positionXElement;
    protected $sizeElement;

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->imageAssetElement = (new OmekaElement\Asset('background_image_asset'))
            ->setAttributes([
                'id' => 'block-layout-data-background-image-asset',
                'data-key' => 'background_image_asset',
            ]);

        $this->positionYElement = (new LaminasElement\Select('background_position_y'))
            ->setEmptyOption('Default') // @translate
            ->setValueOptions([
                'top' => 'Top', // @translate
                'center' => 'Center', // @translate
                'bottom' => 'Bottom', // @translate
            ])
            ->setAttributes([
                'id' => 'block-layout-data-background-position-y',
                'data-key' => 'background_position_y',
            ]);

        $this->positionXElement = (new LaminasElement\Select('background_position_x'))
            ->setEmptyOption('Default') // @translate
            ->setValueOptions([
                'left' => 'Left', // @translate
                'center' => 'Center', // @translate
                'right' => 'Right', // @translate
            ])
            ->setAttributes([
                'id' => 'block-layout-data-background-position-x',
                'data-key' => 'background_position_x',
            ]);
        $this->sizeElement = (new LaminasElement\Select('background_size'))
            ->setEmptyOption('Default') // @translate
            ->setValueOptions([
                'cover' => 'Cover', // @translate
                'contain' => 'Contain', // @translate
            ])
            ->setAttributes([
                'id' => 'block-layout-data-background-size',
                'data-key' => 'background_size',
            ]);
    }

    public function getImageAssetElement()
    {
        return $this->imageAssetElement;
    }

    public function getPositionYElement()
    {
        return $this->positionYElement;
    }

    public function getPositionXElement()
    {
        return $this->positionXElement;
    }

    public function getSizeElement()
    {
        return $this->sizeElement;
    }
}
