<?php
namespace ExtractMetadata\Extractor;

/**
 * Interface for metadata extractors.
 */
interface ExtractorInterface
{
    /**
     * Get the label of this extractor.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Is this extractor available?
     *
     * @return bool
     */
    public function isAvailable();

    /**
     * Does this extractor support this media type?
     *
     * @param string
     * @return bool
     */
    public function supports($mediaType);

    /**
     * Extract metadata from a file and return them as an array.
     *
     * @param string $filePath The path to the file
     * @param string $mediaType The media type of the file
     * @return array
     */
    public function extract($filePath, $mediaType);
}
