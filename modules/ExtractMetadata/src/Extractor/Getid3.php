<?php
namespace ExtractMetadata\Extractor;

/**
 * Use the GetID3 library to extract metadata.
 *
 * @see https://github.com/JamesHeinrich/getID3
 */
class Getid3 implements ExtractorInterface
{
    public function getLabel()
    {
        return 'getID3';
    }

    public function isAvailable()
    {
        return true;
    }

    public function supports($mediaType)
    {
        $getid3 = new \getID3;
        // GetFileFormatArray() returns supported file formats.
        $fileFormats = $getid3->GetFileFormatArray();
        return array_search($mediaType, array_column($fileFormats, 'mime_type'));
    }

    public function extract($filePath, $mediaType)
    {
        $getid3 = new \getID3;
        return $getid3->analyze($filePath);
    }
}
