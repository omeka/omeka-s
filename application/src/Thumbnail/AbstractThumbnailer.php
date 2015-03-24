<?php
namespace Omeka\Thumbnail;

use Omeka\Thumbnail\ThumbnailerInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractThumbnailer implements ThumbnailerInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var array
     */
    protected $options;

    /**
     * {@inheritDoc}
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Get the required offset on the X axis.
     *
     * @param int $width Original image width
     * @param int $size Side size of the square region being selected
     * @param string $gravity
     * @return int
     */
    public function getOffsetX($width, $size, $gravity)
    {
        switch ($gravity) {
            case 'northwest':
            case 'west':
            case 'southwest':
                return 0;
            case 'northeast':
            case 'east':
            case 'southeast':
                return $width - $size;
            case 'north':
            case 'center':
            case 'south':
            default:
                return (int) (($width - $size) / 2);
        }
    }

    /**
     * Get the required offset on the Y axis.
     *
     * @param int $height Original image height
     * @param int $size Side size of square region being selected
     * @param string $gravity
     * @return int
     */
    public function getOffsetY($height, $size, $gravity)
    {
        switch ($gravity) {
            case 'northwest':
            case 'north':
            case 'northeast':
                return 0;
            case 'southwest':
            case 'south':
            case 'southeast':
                return $height - $size;
            case 'west':
            case 'center':
            case 'east':
            default:
                return (int) (($height - $size) / 2);
        }
    }
}
