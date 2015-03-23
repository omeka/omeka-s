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
    public function create($strategy, $constraint, array $options = array())
    {
        switch ($strategy) {
            case 'square':
                $tempPath = $this->createSquare($constraint, $options);
                break;
            case 'default':
            default:
                $tempPath = $this->createDefault($constraint, $options);
        }
        return $tempPath;
    }

    /**
     * Create a default thumbnail.
     *
     * @param int $constraint
     * @return string Path to temporary thumbnail image
     */
    protected function createDefault($constraint, array $options)
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

        return $this->saveTempImage($tempImage);
    }

    /**
     * Create a square thumbnail.
     *
     * @param int $constraint
     * @return string Path to temporary thumbnail image
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

        return $this->saveTempImage($tempImage);
    }

    /**
     * Create a temporary thumbnail image.
     *
     * @param int $width
     * @param int $height
     * @return resource
     */
    protected function createTempImage($width, $height)
    {
        $tempImage = imagecreatetruecolor($width, $height);

        // Replace transparent parts of the image with white instead of black.
        $white = imagecolorallocate($tempImage, 255, 255, 255);
        imagefill($tempImage, 0, 0, $white);

        return $tempImage;
    }

    /**
     * Save a temporary thumbnail image.
     *
     * @param resource $tempImage
     * @return string Path to temporary thumbnail image
     */
    protected function saveTempImage($tempImage)
    {
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
