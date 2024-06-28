<?php
namespace Omeka\File\Thumbnailer;

use Imagick as ImagickPhp;
use ImagickException;
use ImagickPixel;
use Omeka\File\Exception;
use Omeka\File\TempFileFactory;

class Imagick extends AbstractThumbnailer
{
    /**
     * @var TempFileFactory
     */
    protected $tempFileFactory;

    /**
     * Check whether the GD extension is loaded.
     *
     * @throws Exception\InvalidThumbnailer
     */
    public function __construct(TempFileFactory $tempFileFactory)
    {
        if (!extension_loaded('imagick')) {
            throw new Exception\InvalidThumbnailerException('The imagick PHP extension must be loaded to use this thumbnailer.');
        }
        $this->tempFileFactory = $tempFileFactory;
    }

    public function create($strategy, $constraint, array $options = [])
    {
        $mediaType = $this->sourceFile->getMediaType();
        $origPath = sprintf('%s[%s]', $this->source, $this->getOption('page', 0));
        if (strpos($mediaType, 'video/') === 0) {
            $origPath = 'mp4:' . $origPath;
        }

        try {
            $imagick = new ImagickPhp;
            if ($mediaType == 'application/pdf') {
                $imagick->setResolution(150, 150);
                if ($this->getOption('pdfUseCropBox', true)) {
                    $imagick->setOption('pdf:use-cropbox', true);
                }
            }
            $imagick->readImage($origPath);
        } catch (ImagickException $e) {
            throw new Exception\CannotCreateThumbnailException;
        }

        if ($this->getOption('autoOrient', true)) {
            $this->autoOrient($imagick);
        }

        $origWidth = $imagick->getImageWidth();
        $origHeight = $imagick->getImageHeight();

        $imagick->setBackgroundColor('white');
        $imagick->setImageBackgroundColor('white');
        $imagick->setImagePage($origWidth, $origHeight, 0, 0);

        if (defined('Imagick::ALPHACHANNEL_REMOVE')) {
            $imagick->setImageAlphaChannel(ImagickPhp::ALPHACHANNEL_REMOVE);
        } else {
            $imagick = $imagick->mergeImageLayers(ImagickPhp::LAYERMETHOD_FLATTEN);
        }

        switch ($strategy) {
            case 'square':
                $gravity = $options['gravity'] ?? 'center';
                if ($origWidth < $origHeight) {
                    $tempWidth = $constraint;
                    $tempHeight = $origHeight * ($constraint / $origWidth);
                    $origX = 0;
                    $origY = $this->getOffsetY($tempHeight, $constraint, $gravity);
                } else {
                    $tempHeight = $constraint;
                    $tempWidth = $origWidth * ($constraint / $origHeight);
                    $origY = 0;
                    $origX = $this->getOffsetX($tempWidth, $constraint, $gravity);
                }
                $imagick->thumbnailImage(round($tempWidth), round($tempHeight));
                $imagick->cropImage($constraint, $constraint, $origX, $origY);
                $imagick->setImagePage($constraint, $constraint, 0, 0);
                break;
            case 'default':
            default:
                if ($origWidth < $constraint && $origHeight < $constraint) {
                    $imagick->thumbnailImage($origWidth, $origHeight, true);
                } else {
                    $imagick->thumbnailImage($constraint, $constraint, true);
                }
        }

        $tempFile = $this->tempFileFactory->build();
        $tempPath = sprintf('%s.%s', $tempFile->getTempPath(), 'jpg');
        $tempFile->delete();

        $imagick->writeImage($tempPath);
        $imagick->clear();

        return $tempPath;
    }

    /**
     * Detect orientation flag and rotate image accordingly.
     *
     * @param ImagickPhp $imagick
     */
    protected function autoOrient($imagick)
    {
        $orientation = $imagick->getImageOrientation();
        $white = new ImagickPixel('#fff');
        switch ($orientation) {
            case ImagickPhp::ORIENTATION_RIGHTTOP:
                $imagick->rotateImage($white, 90);
                break;
            case ImagickPhp::ORIENTATION_BOTTOMRIGHT:
                $imagick->rotateImage($white, 180);
                break;
            case ImagickPhp::ORIENTATION_LEFTBOTTOM:
                $imagick->rotateImage($white, 270);
                break;
            case ImagickPhp::ORIENTATION_TOPRIGHT:
                $imagick->flopImage();
                break;
            case ImagickPhp::ORIENTATION_RIGHTBOTTOM:
                $imagick->flopImage();
                $imagick->rotateImage($white, 90);
                break;
            case ImagickPhp::ORIENTATION_BOTTOMLEFT:
                $imagick->flopImage();
                $imagick->rotateImage($white, 180);
                break;
            case ImagickPhp::ORIENTATION_LEFTTOP:
                $imagick->flopImage();
                $imagick->rotateImage($white, 270);
                break;
            case ImagickPhp::ORIENTATION_TOPLEFT:
            default:
                break;
        }
    }
}
