<?php
namespace Omeka\Thumbnail\Thumbnailer;

use Omeka\Thumbnail\Exception;
use Omeka\Thumbnail\Thumbnailer\AbstractThumbnailer;

class GdThumbnailer extends AbstractThumbnailer
{
    /**
     * @var "gd" resource
     */
    protected $origImage;

    /**
     * Check whether the GD entension is loaded.
     *
     * @throws Exception\InvalidThumbnailer
     */
    public function __construct()
    {
        if (!extension_loaded('gd')) {
            throw new Exception\InvalidThumbnailerException;
        }
    }

    /**
     * Create image resource.
     *
     * {@inheritDoc}
     */
    public function setSource($source)
    {
        $origImage = @imagecreatefromstring(@file_get_contents($source));
        if (false === $origImage) {
            throw new Exception\CannotCreateThumbnailException;
        }
        $this->origImage = $origImage;
    }

    /**
     * {@inheritDoc}
     */
    public function create($strategy, $constraint, array $options = array())
    {
        switch ($strategy) {
            case 'square':
                $tempImage = $this->createSquare($constraint, $options);
                break;
            case 'default':
            default:
                $tempImage = $this->createDefault($constraint, $options);
        }

        // Save a temporary thumbnail image.
        $file = $this->getServiceLocator()->get('Omeka\TempFile');
        $saveResult = imagejpeg($tempImage, $file->getTempPath());

        if (false === $saveResult) {
            imagedestroy($tempImage);
            throw new Exception\CannotCreateThumbnailException;
        }

        imagedestroy($tempImage);
        return $file->getTempPath();
    }

    /**
     * Create a default thumbnail.
     *
     * @param int $constraint
     * @param array $options
     * @return resource A "gd" resource
     */
    public function createDefault($constraint, array $options)
    {
        $origWidth = imagesx($this->origImage);
        $origHeight = imagesy($this->origImage);

        // Original is landscape
        if ($origWidth > $origHeight) {
            $tempWidth = $constraint;
            $tempHeight = round($origHeight * $constraint / $origWidth);
        }
        // Original is portrait
        elseif ($origWidth < $origHeight) {
            $tempWidth = round($origWidth * $constraint / $origHeight);
            $tempHeight = $constraint;
        }
        // Original is square
        else {
            $tempWidth = $constraint;
            $tempHeight = $constraint;
        }

        $tempImage = $this->createTempImage($tempWidth, $tempHeight);
        $resizeResult = imagecopyresampled($tempImage, $this->origImage, 0, 0,
            0, 0, $tempWidth, $tempHeight, $origWidth, $origHeight);

        if (false === $resizeResult) {
            imagedestroy($tempImage);
            throw new Exception\CannotCreateThumbnailException;
        }

        return $tempImage;
    }

    /**
     * Create a square thumbnail.
     *
     * @param int $constraint
     * @param array $options
     * @return resource A "gd" resource
     */
    public function createSquare($constraint, array $options)
    {
        $gravity = isset($options['gravity']) ? $options['gravity'] : 'center';

        $origWidth = imagesx($this->origImage);
        $origHeight = imagesy($this->origImage);

        // Original is landscape
        if ($origWidth > $origHeight) {
            $origSize = $origHeight;
            $origX = $this->getOffsetX($origWidth, $origSize, $gravity);
            $origY = 0;
        }
        // Original is portrait
        elseif ($origWidth < $origHeight) {
            $origSize = $origWidth;
            $origX = 0;
            $origY = $this->getOffsetY($origHeight, $origSize, $gravity);
        }
        // Original is square
        else {
            $origSize = $origWidth;
            $origX = 0;
            $origY = 0;
        }

        $tempImage = $this->createTempImage($constraint, $constraint);
        $resizeResult = imagecopyresampled($tempImage, $this->origImage, 0, 0,
            $origX, $origY, $constraint, $constraint, $origSize, $origSize);

        if (false === $resizeResult) {
            imagedestroy($tempImage);
            throw new Exception\CannotCreateThumbnailException;
        }

        return $tempImage;
    }

    /**
     * Create a temporary thumbnail image.
     *
     * @param int $width
     * @param int $height
     * @return resource
     */
    public function createTempImage($width, $height)
    {
        $tempImage = imagecreatetruecolor($width, $height);

        // Replace transparent parts of the image with white instead of black.
        $white = imagecolorallocate($tempImage, 255, 255, 255);
        imagefill($tempImage, 0, 0, $white);

        return $tempImage;
    }

    /**
     * Destroy the GD resource.
     *
     * This works because the gd thumbnailer is a non-shared service.
     */
    public function __destruct()
    {
        if (is_resource($this->origImage)) {
            imagedestroy($this->origImage);
        }
    }
}
