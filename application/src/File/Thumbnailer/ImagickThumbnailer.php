<?php
namespace Omeka\File\Thumbnailer;

use Imagick;
use ImagickException;
use Omeka\File\Exception;
use Omeka\File\Manager as FileManager;

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
            throw new Exception\InvalidThumbnailerException('The imagick PHP extension must be loaded to use this thumbnailer.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function create(FileManager $fileManager, $strategy, $constraint, array $options = [])
    {
        try {
            $imagick = new Imagick(sprintf('%s[%s]', $this->source, $this->getOption('page', 0)));
        } catch (ImagickException $e) {
            throw new Exception\CannotCreateThumbnailException;
        }

        $origWidth = $imagick->getImageWidth();
        $origHeight = $imagick->getImageHeight();

        $imagick->setBackgroundColor('white');
        $imagick->setImageBackgroundColor('white');
        $imagick->setImagePage($origWidth, $origHeight, 0, 0);
        $imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

        switch ($strategy) {
            case 'square':
                $gravity = isset($options['gravity']) ? $options['gravity'] : 'center';
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
                $imagick->thumbnailImage($tempWidth, $tempHeight);
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

        $file = $fileManager->getTempFile();
        $tempPath = sprintf('%s.%s', $file->getTempPath(), FileManager::THUMBNAIL_EXTENSION);
        $file->delete();

        $imagick->writeImage($tempPath);
        $imagick->clear();

        return $tempPath;
    }
}
