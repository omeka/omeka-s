<?php declare(strict_types=1);

namespace Omeka\File\Thumbnailer;

use Omeka\File\Exception;
use Omeka\File\TempFileFactory;

class Auto extends AbstractThumbnailer
{
    /**
     * @var TempFileFactory
     */
    protected $tempFileFactory;

    /**
     * @var array
     */
    protected $thumbnailers;

    public function __construct(TempFileFactory $tempFileFactory, array $thumbnailers)
    {
        $this->tempFileFactory = $tempFileFactory;
        $this->thumbnailers = $thumbnailers;
    }

    public function create($strategy, $constraint, array $options = [])
    {
        $mediaType = $this->sourceFile->getMediaType();

        foreach ($this->thumbnailers as $name => $thumbnailer) {
            if (in_array($mediaType, $thumbnailer['supported'])) {
                $thumbnailer['thumbnailer']->setSource($this->sourceFile);
                return $thumbnailer['thumbnailer']->create($strategy, $constraint, $options);
            }
            if ($thumbnailer['dynamic'] && !in_array($mediaType, $thumbnailer['unsupported'])) {
                try {
                    $thumbnailer['thumbnailer']->setSource($this->sourceFile);
                    return $thumbnailer['thumbnailer']->create($strategy, $constraint, $options);
                } catch (Exception\CannotCreateThumbnailException $e) {
                    // Avoid to check the same media type multiple times in case
                    // of a bulk import.
                    $this->thumbnailers[$name]['unsupported'][] = $mediaType;
                }
            }
        }

        throw new Exception\CannotCreateThumbnailException;
    }
}
