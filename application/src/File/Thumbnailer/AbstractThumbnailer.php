<?php
namespace Omeka\File\Thumbnailer;

use Omeka\File\TempFile;

abstract class AbstractThumbnailer implements ThumbnailerInterface
{
    /**
     * @var string
     */
    protected $source;

    /**
     * @var TempFile
     */
    protected $sourceFile;

    /**
     * @var array
     */
    protected $options;

    /**
     * {@inheritDoc}
     */
    public function setSource(TempFile $source)
    {
        $this->source = $source->getTempPath();
        $this->sourceFile = $source;
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(array $options)
    {
        // Set options only once for shared services.
        if (!isset($this->options)) {
            $this->options = $options;
        }
    }

    /**
     * Get an option.
     *
     * @param string $option
     * @param string $default
     * @return string
     */
    public function getOption($option, $default = null)
    {
        return isset($this->options[$option]) ? $this->options[$option] : $default;
    }

    /**
     * For the square strategy, get the required offset on the X axis.
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
     * For the square strategy, get the required offset on the Y axis.
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
