<?php
namespace ExtractMetadata\Extractor;

/**
 * Use PHP's exif library to extract metadata.
 *
 * @see https://www.php.net/manual/en/book.exif.php
 */
class Exif implements ExtractorInterface
{
    public function getLabel()
    {
        return 'Exif';
    }

    public function isAvailable()
    {
        return extension_loaded('exif');
    }

    public function supports($mediaType)
    {
        return in_array($mediaType, [
            'image/jpeg',
            'image/tiff',
        ]);
    }

    public function extract($filePath, $mediaType)
    {
        return exif_read_data($filePath, null, true);
    }
}
