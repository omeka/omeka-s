<?php
namespace Omeka\Form\Element;

use Laminas\Form\Element as LaminasElement;
use Omeka\Form\Element as OmekaElement;

class BackgroundImage extends LaminasElement
{
    protected $assetElement;
    protected $positionYElement;
    protected $positionXElement;
    protected $sizeElement;

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->assetElement = (new OmekaElement\Asset('background_image_asset'))
            ->setAttributes([
                'id' => 'block-layout-data-background-image-asset',
                'data-key' => 'background_image_asset',
            ]);

        $this->positionYElement = (new LaminasElement\Select('background_image_position_y'))
            ->setEmptyOption('Default') // @translate
            ->setValueOptions([
                'top' => 'Top', // @translate
                'center' => 'Center', // @translate
                'bottom' => 'Bottom', // @translate
            ])
            ->setAttributes([
                'id' => 'block-layout-data-background-image-position-y',
                'data-key' => 'background_image_position_y',
            ]);

        $this->positionXElement = (new LaminasElement\Select('background_image_position_x'))
            ->setEmptyOption('Default') // @translate
            ->setValueOptions([
                'left' => 'Left', // @translate
                'center' => 'Center', // @translate
                'right' => 'Right', // @translate
            ])
            ->setAttributes([
                'id' => 'block-layout-data-background-image-position-x',
                'data-key' => 'background_image_position_x',
            ]);
        $this->sizeElement = (new LaminasElement\Select('background_image_size'))
            ->setEmptyOption('Default') // @translate
            ->setValueOptions([
                'cover' => 'Cover', // @translate
                'contain' => 'Contain', // @translate
            ])
            ->setAttributes([
                'id' => 'block-layout-data-background-image-size',
                'data-key' => 'background_image_size',
            ]);
    }

    public function getAssetElement()
    {
        return $this->assetElement;
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
