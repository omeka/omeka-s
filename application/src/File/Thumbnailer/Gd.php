<?php
namespace Omeka\File\Thumbnailer;

use Omeka\File\Exception;
use Omeka\File\TempFile;
use Omeka\File\TempFileFactory;

class Gd extends AbstractThumbnailer
{
    /**
     * @var TempFileFactory
     */
    protected $tempFileFactory;

    /**
     * @var resource
     */
    protected $origImage;

    /**
     * @var int The width of the original image
     */
    protected $origWidth;

    /**
     * @var int The height of the original image
     */
    protected $origHeight;

    /**
     * @var string The profile icc of the original image.
     */
    protected $icc;

    /**
     * @var int The exif orientation of the original image.
     */
    protected $orientation = 1;

    /**
     * Check whether the GD extension is loaded.
     *
     * @throws Exception\InvalidThumbnailer
     */
    public function __construct(TempFileFactory $tempFileFactory)
    {
        if (!extension_loaded('gd')) {
            throw new Exception\InvalidThumbnailerException('The GD PHP extension must be loaded to use this thumbnailer.');
        }
        $this->tempFileFactory = $tempFileFactory;
    }

    /**
     * Create image resource.
     *
     * {@inheritDoc}
     */
    public function setSource(TempFile $source)
    {
        $mediaType = $source->getMediaType();
        $sourcePath = $source->getTempPath();

        switch ($mediaType) {
            case 'image/gif':
                $origImage = imagecreatefromgif($sourcePath);
                break;
            case 'image/jpeg':
                $origImage = imagecreatefromjpeg($sourcePath);
                $inputPel = new \lsolesen\pel\PelJpeg($sourcePath);
                $this->icc = $inputPel->getIcc();
                $this->orientation = $this->getExifOrientation($inputPel);
                break;
            case 'image/png':
                $origImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $origImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                throw new Exception\CannotCreateThumbnailException(
                    sprintf('Cannot create thumbnail for type "%s"', $mediaType)
                );
        }
        if (false === $origImage) {
            throw new Exception\CannotCreateThumbnailException;
        }

        $this->origImage = $origImage;
        $this->origWidth = imagesx($origImage);
        $this->origHeight = imagesy($origImage);
    }

    public function create($strategy, $constraint, array $options = [])
    {
        if ($this->orientation > 1 && $this->getOption('autoOrient', true)) {
            $this->autoOrient();
        }

        switch ($strategy) {
            case 'square':
                $tempImage = $this->createSquare($constraint, $options);
                break;
            case 'default':
            default:
                $tempImage = $this->createDefault($constraint, $options);
        }

        // Save a temporary thumbnail image.
        $tempFile = $this->tempFileFactory->build();
        $saveResult = imagejpeg($tempImage, $tempFile->getTempPath());
        unset($tempImage);

        if (false === $saveResult) {
            throw new Exception\CannotCreateThumbnailException;
        }

        $tempPath = $tempFile->getTempPath();
        if ($this->icc) {
            $outputPel = new \lsolesen\pel\PelJpeg($tempPath);
            $outputPel->setIcc($this->icc);
            $outputPel->saveFile($tempPath);
        }

        return $tempPath;
    }

    /**
     * Get the exif orientation of a jpeg image, 1 when there is none.
     *
     * The exif extension is not required: the data are read with the library
     * that is already used to manage the icc profile.
     *
     * @return int
     */
    protected function getExifOrientation(\lsolesen\pel\PelJpeg $pelJpeg)
    {
        try {
            $exif = $pelJpeg->getExif();
            $tiff = $exif ? $exif->getTiff() : null;
            $ifd = $tiff ? $tiff->getIfd() : null;
            $entry = $ifd ? $ifd->getEntry(\lsolesen\pel\PelTag::ORIENTATION) : null;
            $orientation = $entry ? (int) $entry->getValue() : 1;
        } catch (\Throwable $e) {
            return 1;
        }
        return $orientation >= 1 && $orientation <= 8 ? $orientation : 1;
    }

    /**
     * Rotate and flip the original image according to the exif orientation.
     *
     * The thumbnail is saved without exif data, so the transformation must be
     * applied to the pixels. It is done once, even when multiple thumbnails
     * are created from the same source.
     */
    protected function autoOrient()
    {
        // Rotations are counterclockwise with imagerotate(), unlike exif.
        switch ($this->orientation) {
            case 2:
                imageflip($this->origImage, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $this->rotate(180);
                break;
            case 4:
                imageflip($this->origImage, IMG_FLIP_VERTICAL);
                break;
            case 5:
                $this->rotate(270);
                imageflip($this->origImage, IMG_FLIP_HORIZONTAL);
                break;
            case 6:
                $this->rotate(270);
                break;
            case 7:
                $this->rotate(270);
                imageflip($this->origImage, IMG_FLIP_VERTICAL);
                break;
            case 8:
                $this->rotate(90);
                break;
            case 1:
            default:
                break;
        }

        $this->orientation = 1;
        $this->origWidth = imagesx($this->origImage);
        $this->origHeight = imagesy($this->origImage);
    }

    /**
     * Rotate the original image counterclockwise by a multiple of 90 degrees.
     *
     * @param int $degrees
     */
    protected function rotate($degrees)
    {
        $rotated = imagerotate($this->origImage, $degrees, 0);
        if ($rotated === false) {
            throw new Exception\CannotCreateThumbnailException;
        }
        $this->origImage = $rotated;
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
        if ($this->origWidth < $constraint && $this->origHeight < $constraint) {
            // Original is smaller than constraint
            $tempWidth = $this->origWidth;
            $tempHeight = $this->origHeight;
        } elseif ($this->origWidth > $this->origHeight) {
            // Original is smaller than constraint
            $tempWidth = $constraint;
            $tempHeight = round($this->origHeight * $constraint / $this->origWidth);
        } elseif ($this->origWidth < $this->origHeight) {
            // Original is portrait
            $tempWidth = round($this->origWidth * $constraint / $this->origHeight);
            $tempHeight = $constraint;
        } else {
            // Original is square
            $tempWidth = $constraint;
            $tempHeight = $constraint;
        }

        $tempImage = $this->createTempImage($tempWidth, $tempHeight);
        $resizeResult = imagecopyresampled($tempImage, $this->origImage, 0, 0,
            0, 0, $tempWidth, $tempHeight, $this->origWidth, $this->origHeight);

        if (false === $resizeResult) {
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
        $gravity = $options['gravity'] ?? 'center';

        // Original is landscape
        if ($this->origWidth > $this->origHeight) {
            $origSize = $this->origHeight;
            $origX = $this->getOffsetX($this->origWidth, $origSize, $gravity);
            $origY = 0;
        }
        // Original is portrait
        elseif ($this->origWidth < $this->origHeight) {
            $origSize = $this->origWidth;
            $origX = 0;
            $origY = $this->getOffsetY($this->origHeight, $origSize, $gravity);
        }
        // Original is square
        else {
            $origSize = $this->origWidth;
            $origX = 0;
            $origY = 0;
        }

        $tempImage = $this->createTempImage($constraint, $constraint);
        $resizeResult = imagecopyresampled($tempImage, $this->origImage, 0, 0,
            $origX, $origY, $constraint, $constraint, $origSize, $origSize);

        if (false === $resizeResult) {
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
}
