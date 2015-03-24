<?php
namespace Omeka\Thumbnail;

use Imagick;
use Omeka\Thumbnail\Exception;
use Omeka\Thumbnail\ThumbnailerInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class ImagickThumbnailer implements ThumbnailerInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var int
     */
    protected $page = 0;

    /**
     * Check whether the GD entension is loaded.
     *
     * @throws Exception\InvalidThumbnailer
     */
    public function __construct()
    {
        if (!extension_loaded('imagick')) {
            throw new Exception\InvalidThumbnailerException;
        }
    }

    /**
     * Create Imagick instance.
     *
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
        if (isset($options['page'])) {
            $this->page = (int) $options['page'];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function create($strategy, $constraint, array $options = array())
    {
        $imagick = new Imagick(sprintf('%s[%s]', $this->source, $this->page));
        $imagick->setBackgroundColor('white');
        $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

        switch ($strategy) {
            case 'square':
                $gravity = isset($options['gravity']) ? $options['gravity'] : 'center';
                $origWidth = $imagick->getImageWidth();
                $origHeight = $imagick->getImageHeight();
                if ($origWidth < $origHeight) {
                    $tempHeight = $constraint;
                    $tempWidth = $origHeight * ($constraint / $origWidth);
                    $origX = 0;
                    $origY = $this->getOffsetY($tempWidth, $constraint, $gravity);
                } else {
                    $tempHeight = $constraint;
                    $tempWidth = $origWidth * ($constraint / $origHeight);
                    $origY = 0;
                    $origX = $this->getOffsetX($tempWidth, $constraint, $gravity);
                }
                $imagick->thumbnailImage($tempWidth, $tempHeight);
                $imagick->cropImage($constraint, $constraint, $origX, $origY);
                $imagick->setImagePage($constraint, $constraint, 0, 0);
                break;
            case 'default':
            default:
                $imagick->thumbnailImage($constraint, $constraint, true);
        }

        $file = $this->getServiceLocator()->get('Omeka\StorableFile');
        $imagick->writeImage($file->getTempPath());
        $imagick->clear();

        return $file->getTempPath();
    }

    /**
     * Get the required offset on the X axis.
     *
     * This respects the "gravity" option.
     *
     * @param int $width Original image width
     * @param int $size Side size of the square region being selected
     * @param string $gravity
     * @return int
     */
    protected function getOffsetX($width, $size, $gravity)
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
     * This respects the "gravity" option.
     *
     * @param int $height Original image height
     * @param int $size Side size of square region being selected
     * @param string $gravity
     * @return int
     */
    protected function getOffsetY($height, $size, $gravity)
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
