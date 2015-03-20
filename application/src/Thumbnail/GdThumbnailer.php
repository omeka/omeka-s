<?php
namespace Omeka\Thumbnail;

use Omeka\Thumbnail\Exception;
use Omeka\Thumbnail\ThumbnailerInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class GdThumbnailer implements ThumbnailerInterface
{
    use ServiceLocatorAwareTrait;

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
    public function create($type, $constraint)
    {
        switch ($type) {
            case 'square':
                $tempPath = $this->createSquare($constraint);
                break;
            case 'default':
            default:
                $tempPath = $this->createDefault($constraint);
        }
        return $tempPath;
    }

    /**
     * Create a default thumbnail.
     *
     * @param int $constraint
     */
    protected function createDefault($constraint)
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

        $tempImage = imagecreatetruecolor($tempWidth, $tempHeight);

        // Replace transparent parts of the image with white instead of black.
        $white = imagecolorallocate($tempImage, 255, 255, 255);
        imagefill($tempImage, 0, 0, $white);

        $resizeResult = imagecopyresampled($tempImage, $this->origImage, 0, 0,
            0, 0, $tempWidth, $tempHeight, $origWidth, $origHeight);

        if (false === $resizeResult) {
            imagedestroy($tempImage);
            throw new Exception\CannotCreateThumbnailException;
        }

        $file = $this->getServiceLocator()->get('Omeka\StorableFile');
        $saveResult = imagejpeg($tempImage, $file->getTempPath());

        if (false === $saveResult) {
            imagedestroy($tempImage);
            throw new Exception\CannotCreateThumbnailException;
        }

        imagedestroy($tempImage);
        return $file->getTempPath();
    }

    /**
     * Destroy the GD resource.
     */
    public function __destruct()
    {
        if (is_resource($this->origImage)) {
            imagedestroy($this->origImage);
        }
    }
}
