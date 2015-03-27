<?php
namespace Omeka\Thumbnail\Thumbnailer;

use Imagick;
use ImagickException;
use Omeka\Thumbnail\Exception;
use Omeka\Thumbnail\Manager as ThumbnailManager;
use Omeka\Thumbnail\Thumbnailer\AbstractThumbnailer;

class ImagickThumbnailer extends AbstractThumbnailer
{
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
    public function create($strategy, $constraint, array $options = array())
    {
        try {
            $imagick = new Imagick(sprintf('%s[%s]', $this->source, $this->getOption('page', 0)));
        } catch (ImagickException $e) {
            throw new Exception\CannotCreateThumbnailException;
        }
        $imagick->setBackgroundColor('white');
        $imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

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
        $tempPath = $file->getTempPath() . ThumbnailManager::EXTENSION;
        $imagick->writeImage($tempPath);
        $imagick->clear();

        return $tempPath;
    }
}
