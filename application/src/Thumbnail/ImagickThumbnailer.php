<?php
namespace Omeka\Thumbnail;

use Imagick;
use ImagickException;
use Omeka\Thumbnail\Exception;
use Omeka\Thumbnail\AbstractThumbnailer;

class ImagickThumbnailer extends AbstractThumbnailer
{
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
        try {
            $imagick = new Imagick(sprintf('%s[%s]', $this->source, $this->page));
        } catch (ImagickException $e) {
            throw new Exception\CannotCreateThumbnailException;
        }
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
}
