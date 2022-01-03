<?php
namespace Omeka\File;

use finfo;
use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Store\StoreInterface;
use Omeka\Stdlib\ErrorStore;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\Math\Rand;
use XMLReader;

class TempFile
{
    use EventManagerAwareTrait;

    /**
     * Map of nonstandard-to-standard media types.
     */
    const MEDIA_TYPE_ALIASES = [
        // application/ogg
        'application/x-ogg' => 'application/ogg',
        // application/rtf
        'text/rtf' => 'application/rtf',
        // audio/midi
        'audio/mid' => 'audio/midi',
        'audio/x-midi' => 'audio/midi',
        // audio/mpeg
        'audio/mp3' => 'audio/mpeg',
        'audio/mpeg3' => 'audio/mpeg',
        'audio/x-mp3' => 'audio/mpeg',
        'audio/x-mpeg' => 'audio/mpeg',
        'audio/x-mpeg3' => 'audio/mpeg',
        'audio/x-mpegaudio' => 'audio/mpeg',
        'audio/x-mpg' => 'audio/mpeg',
        // audio/ogg
        'audio/x-ogg' => 'audio/ogg',
        // audio/x-aac
        'audio/aac' => 'audio/x-aac',
        // audio/x-aiff
        'audio/aiff' => 'audio/x-aiff',
        // audio/x-ms-wma
        'audio/x-wma' => 'audio/x-ms-wma',
        'audio/wma' => 'audio/x-ms-wma',
        // audio/mp4
        'audio/x-mp4' => 'audio/mp4',
        'audio/x-m4a' => 'audio/mp4',
        // audio/x-wav
        'audio/wav' => 'audio/x-wav',
        // image/bmp
        'image/x-ms-bmp' => 'image/bmp',
        // image/x-icon
        'image/icon' => 'image/x-icon',
        // video/mp4
        'video/x-m4v' => 'video/mp4',
        // video/x-ms-asf
        'video/asf' => 'video/x-ms-asf',
        // video/x-ms-wmv
        'video/wmv' => 'video/x-ms-wmv',
        // video/x-msvideo
        'video/avi' => 'video/x-msvideo',
        'video/msvideo' => 'video/x-msvideo',
    ];

    /**
     * Map the output from xml checker and standard xml media types.
     *
     * Xml media types are generally not registered, so the unregistered tree
     * (prefix "x") is used, except when the format is public, in which case the
     * vendor tree is used (prefix "vnd").
     *
     * @var array
     */
    protected $xmlMediaTypes = [
        'application/xml' => 'application/xml',
        'text/xml' => 'text/xml',
        // Common (if not managed by fileinfo).
        'http://www.w3.org/2000/svg' => 'image/svg+xml',
        'application/vnd.oasis.opendocument.presentation' => 'application/vnd.oasis.opendocument.presentation-flat-xml',
        'application/vnd.oasis.opendocument.spreadsheet' => 'application/vnd.oasis.opendocument.spreadsheet-flat-xml',
        'application/vnd.oasis.opendocument.text' => 'application/vnd.oasis.opendocument.text-flat-xml',
        'http://www.w3.org/1999/xhtml' => 'application/xhtml+xml',
        'http://www.w3.org/2005/Atom' => 'application/atom+xml',
        'http://purl.org/rss/1.0/' => 'application/rss+xml',
        // Common in library and culture world.
        'http://bibnum.bnf.fr/ns/alto_prod' => 'application/alto+xml',
        'http://bibnum.bnf.fr/ns/refNum' => 'application/vnd.bnf.refnum+xml',
        'http://www.iccu.sbn.it/metaAG1.pdf' => 'application/vnd.iccu.mag+xml',
        'http://www.loc.gov/MARC21/slim' => 'application/marcxml+xml',
        'http://www.loc.gov/METS/' => 'application/mets+xml',
        'http://www.loc.gov/mods/' => 'application/mods+xml',
        'http://www.loc.gov/standards/alto/ns-v3#' => 'application/alto+xml',
        'http://www.music-encoding.org/ns/mei' => 'application/vnd.mei+xml',
        'http://www.music-encoding.org/schema/3.0.0/mei-all.rng' => 'application/vnd.mei+xml',
        // See https://github.com/w3c/musicxml/blob/gh-pages/schema/musicxml.xsd
        'http://www.musicxml.org/xsd/MusicXML' => 'application/vnd.recordare.musicxml+xml',
        'http://www.openarchives.org/OAI/2.0/' => 'application/vnd.openarchives.oai-pmh+xml',
        'http://www.openarchives.org/OAI/2.0/static-repository' => 'application/vnd.openarchives.oai-pmh+xml',
        'http://www.tei-c.org/ns/1.0' => 'application/tei+xml',
        // Omeka should support itself.
        'http://omeka.org/schemas/omeka-xml/v1' => 'text/vnd.omeka+xml',
        'http://omeka.org/schemas/omeka-xml/v2' => 'text/vnd.omeka+xml',
        'http://omeka.org/schemas/omeka-xml/v3' => 'text/vnd.omeka+xml',
        'http://omeka.org/schemas/omeka-xml/v4' => 'text/vnd.omeka+xml',
        'http://omeka.org/schemas/omeka-xml/v5' => 'text/vnd.omeka+xml',
        // Doctype and root elements in case there is no namespace.
        'alto' => 'application/alto+xml',
        'ead' => 'application/vnd.ead+xml',
        'feed' => 'application/atom+xml',
        'html' => 'text/html',
        'mag' => 'application/vnd.iccu.mag+xml',
        'mei' => 'application/vnd.mei+xml',
        'mets' => 'application/mets+xml',
        'mods' => 'application/mods+xml',
        'pdf2xml' => 'application/vnd.pdf2xml+xml',
        'refNum' => 'application/vnd.bnf.refnum+xml',
        'rss' => 'application/rss+xml',
        'score-partwise' => 'application/vnd.recordare.musicxml+xml',
        'svg' => 'image/svg+xml',
        'TEI' => 'application/tei+xml',
        // 'collection' => 'application/vnd.marc21+xml',
        'xhtml' => 'application/xhtml+xml',
    ];

    /**
     * @var StoreInterface
     */
    protected $store;

    /**
     * @var ThumbnailManager
     */
    protected $thumbnailManager;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var string Directory where to save this temporary file
     */
    protected $tempDir;

    /**
     * @var string Path to this temprorary file
     */
    protected $tempPath;

    /**
     * @var array Media type map
     */
    protected $mediaTypeMap;

    /**
     * @var string The name of the original source file
     */
    protected $sourceName;

    /**
     * @var string Base name of the stored file (without extension)
     */
    protected $storageId;

    /**
     * @var string Internet media type of the file
     */
    protected $mediaType;

    /**
     * @var string Extension of the file
     */
    protected $extension;

    /**
     * @param string $tempDir
     * @param array $mediaTypeMap
     * @param StoreInterface $store
     * @param ThumbnailManager $thumbnailManager
     * @param Validator $validator
     */
    public function __construct($tempDir, array $mediaTypeMap,
        StoreInterface $store, ThumbnailManager $thumbnailManager,
        Validator $validator
    ) {
        $this->tempDir = $tempDir;
        $this->mediaTypeMap = $mediaTypeMap;
        $this->store = $store;
        $this->thumbnailManager = $thumbnailManager;
        $this->validator = $validator;

        // Always create a new, uniquely named temporary file.
        $this->setTempPath(tempnam($tempDir, 'omeka'));
    }

    /**
     * Set the path to the temporary file.
     *
     * Typically needed only when the temporary file already exists on the
     * server.
     *
     * @param string $tempPath
     */
    public function setTempPath($tempPath)
    {
        $this->tempPath = $tempPath;
    }

    /**
     * Get the path to the temporary file.
     *
     * @param null|string $tempDir
     * @return string
     */
    public function getTempPath()
    {
        return $this->tempPath;
    }

    /**
     * Get the name/path of the source file.
     *
     * @return string
     */
    public function getSourceName()
    {
        return $this->sourceName;
    }

    /**
     * Set the name/path of the source file.
     *
     * @param string $sourceName
     */
    public function setSourceName($sourceName)
    {
        $this->sourceName = $sourceName;
    }

    /**
     * Get the storage ID.
     *
     * The storage ID is the base name (without extension) of the persistently
     * stored file.
     *
     * @return string
     */
    public function getStorageId()
    {
        if (isset($this->storageId)) {
            return $this->storageId;
        }
        $this->storageId = bin2hex(Rand::getBytes(20));
        return $this->storageId;
    }

    /**
     * Set the storage ID
     *
     * @param string $storageId
     */
    public function setStorageId($storageId)
    {
        $this->storageId = $storageId;
    }

    /**
     * Store a file.
     *
     * @param string $prefix The storage prefix
     * @param null|string $extension The file extension, if different file
     * @param null|string $tempPath The temp path, if different file
     * @return string The path of the stored file
     */
    public function store($prefix, $extension = null, $tempPath = null)
    {
        if (null === $extension) {
            $extension = $this->getExtension(); // could return null
        }
        if (null !== $extension) {
            $extension = ".$extension";
        }
        if (null === $tempPath) {
            $tempPath = $this->getTempPath();
        }
        $storagePath = sprintf('%s/%s%s', $prefix, $this->getStorageId(), $extension);
        $this->store->put($tempPath, $storagePath);
        return $storagePath;
    }

    /**
     * Store this as an "original" file.
     *
     * @return string The path of the stored file
     */
    public function storeOriginal()
    {
        return $this->store('original');
    }

    /**
     * Store this as an "asset" file.
     *
     * @return string The path of the stored file
     */
    public function storeAsset()
    {
        return $this->store('asset');
    }

    /**
     * Create and store thumbnail derivatives of this file.
     *
     * @return bool Whether thumbnails were created and stored
     */
    public function storeThumbnails()
    {
        $thumbnailer = $this->thumbnailManager->buildThumbnailer();

        $tempPaths = [];

        try {
            $thumbnailer->setSource($this);
            $thumbnailer->setOptions($this->thumbnailManager->getThumbnailerOptions());
            foreach ($this->thumbnailManager->getTypeConfig() as $type => $config) {
                $tempPaths[$type] = $thumbnailer->create(
                    $config['strategy'], $config['constraint'], $config['options']
                );
            }
        } catch (Exception\CannotCreateThumbnailException $e) {
            // Delete temporary files created before exception was thrown.
            foreach ($tempPaths as $tempPath) {
                @unlink($tempPath);
            }
            return false;
        }

        // Finally, store the thumbnails.
        foreach ($tempPaths as $type => $tempPath) {
            $this->store($type, 'jpg', $tempPath);
            // Delete the temporary file in case the file store hasn't already.
            @unlink($tempPath);
        }

        return true;
    }

    /**
     * Get the Internet media type of the file.
     *
     * @uses finfo
     * @return string
     */
    public function getMediaType()
    {
        if (isset($this->mediaType)) {
            return $this->mediaType;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mediaType = $finfo->file($this->getTempPath());
        if (array_key_exists($mediaType, self::MEDIA_TYPE_ALIASES)) {
            $mediaType = self::MEDIA_TYPE_ALIASES[$mediaType];
        }
        if ($mediaType === 'text/xml' || $mediaType === 'application/xml') {
            $mediaType = $this->getMediaTypeXml() ?: $mediaType;
        }
        if ($mediaType === 'application/zip') {
            $mediaType = $this->getMediaTypeZip() ?: $mediaType;
        }
        $this->mediaType = $mediaType;
        return $this->mediaType;
    }

    /**
     * Get the filename extension for the original file.
     *
     * Heuristically determines whether the passed file has an extension. The
     * source name must contain at least one dot, the source name must not end
     * with a dot, and the extension must not be over 12 characters.
     *
     * Returns the extension if found. Returns a "best guess" extension if the
     * media type is known but the original extension is not found. Returns
     * null if the file has no source name or the file has no extension and the
     * media type cannot be mapped to an extension.
     *
     * @return string|null
     */
    public function getExtension()
    {
        if (isset($this->extension)) {
            return $this->extension;
        }
        $extension = null;
        if (!$sourceName = $this->getSourceName()) {
            return $extension;
        }
        $dotPos = strrpos($sourceName, '.');
        if (false !== $dotPos) {
            $sourceNameLen = strlen($sourceName);
            $extensionPos = $dotPos + 1;
            if ($sourceNameLen !== $extensionPos && (12 >= $sourceNameLen - $extensionPos)) {
                $extension = strtolower(substr($sourceName, $extensionPos));
            }
        }
        if (null === $extension) {
            $mediaType = $this->getMediaType();
            if (isset($this->mediaTypeMap[$mediaType][0])) {
                $extension = strtolower($this->mediaTypeMap[$mediaType][0]);
            }
        }
        return $extension;
    }

    /**
     * Get the SHA-256 checksum of the file.
     *
     * @uses hash_file
     * @return string
     */
    public function getSha256()
    {
        return hash_file('sha256', $this->getTempPath());
    }

    /**
     * Get the size of the file in bytes.
     *
     * @uses filesize
     * @return string
     */
    public function getSize()
    {
        return filesize($this->getTempPath());
    }

    /**
     * Delete this temporary file.
     *
     * Always delete a temporary file after all work has been done. Otherwise
     * the file will remain in the temporary directory.
     *
     * @return bool Whether the file was deleted/never created
     */
    public function delete()
    {
        if (isset($this->tempPath)) {
            return unlink($this->tempPath);
        }
        return true;
    }

    /**
     * Ingest a media file.
     *
     * Ingesters should use this method at the end of ingest() to validate the
     * file (if applicable), to set the storage ID to the media, and any of the
     * following, depending on flags:
     *   - store the original file
     *   - create and store thumbnail files
     *   - set file metadata to the media
     *   - delete the temporary file
     *
     * @param Media $media
     * @param Request $request
     * @param ErrorStore $errorStore
     * @param bool $storeOriginal Store original file
     * @param bool $storeThumbnails Store thumbnail images?
     * @param bool $deleteTempFile Delete the temp file after ingest?
     * @param bool $hydrateFileMetadataOnStoreOriginalFalse
     */
    public function mediaIngestFile(Media $media, Request $request,
        ErrorStore $errorStore, $storeOriginal = true, $storeThumbnails = true,
        $deleteTempFile = true, $hydrateFileMetadataOnStoreOriginalFalse = false
    ) {
        try {
            if (($storeOriginal || $hydrateFileMetadataOnStoreOriginalFalse)
                && !$this->validator->validate($this, $errorStore)
            ) {
                // The file does not validate.
                return;
            }
            $eventParams = [
                'tempFile' => $this,
                'request' => $request,
                'errorStore' => $errorStore,
                'storeOriginal' => $storeOriginal,
                'storeThumbnails' => $storeThumbnails,
                'deleteTempFile' => $deleteTempFile,
            ];
            $event = new Event('media.ingest_file.pre', $media, $eventParams);
            $this->getEventManager()->triggerEvent($event);

            $media->setStorageId($this->getStorageId());
            if ($storeOriginal || $hydrateFileMetadataOnStoreOriginalFalse) {
                $media->setExtension($this->getExtension());
                $media->setMediaType($this->getMediaType());
                $media->setSha256($this->getSha256());
                $media->setSize($this->getSize());
            }
            if ($storeOriginal) {
                $this->storeOriginal();
                $media->setHasOriginal(true);
            }
            if ($storeThumbnails) {
                $hasThumbnails = $this->storeThumbnails();
                $media->setHasThumbnails($hasThumbnails);
            }
        } finally {
            if ($deleteTempFile) {
                $this->delete();
            }
        }

        $event = new Event('media.ingest_file.post', $media, $eventParams);
        $this->getEventManager()->triggerEvent($event);
    }

    /**
     * Extract a more precise xml media type when possible.
     *
     * @return string
     */
    protected function getMediaTypeXml()
    {
        $filepath = $this->getTempPath();

        libxml_clear_errors();

        $reader = new XMLReader();
        if (!$reader->open($filepath)) {
            // TODO The logger is not available.
            return null;
        }

        $type = null;

        // Don't output error in case of a badly formatted file since there is no logger.
        while (@$reader->read()) {
            if ($reader->nodeType === XMLReader::DOC_TYPE) {
                $type = $reader->name;
                break;
            }

            // To be improved or skipped.
            if ($reader->nodeType === XMLReader::PI
                && !in_array($reader->name, [
                    'xml-model',
                    'xml-stylesheet',
                    'oxygen',
                ])
            ) {
                $matches = [];
                if (preg_match('~href="(.+?)"~mi', $reader->value, $matches)) {
                    $type = $matches[1];
                    break;
                }
            }

            if ($reader->nodeType === XMLReader::ELEMENT) {
                if ($reader->namespaceURI === 'urn:oasis:names:tc:opendocument:xmlns:office:1.0') {
                    $type = $reader->getAttributeNs('mimetype', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
                } else {
                    $type = $reader->namespaceURI ?: $reader->getAttribute('xmlns');
                }
                if (!$type) {
                    $type = $reader->name;
                }
                break;
            }
        }

        $reader->close();

        /*
        // TODO The logger is not available.
        $error = libxml_get_last_error();
        if ($error) {
            $message = new \Omeka\Stdlib\PsrMessage(
                'Error level {level}, code {code}, for file "{file}", line {line}, column {column}: {message}',
                ['level' => $error->level, 'code' => $error->code, 'file' => $error->file, 'line' => $error->line, 'column' => $error->column, 'message' => $error->message]
            );
            $this->logger->err($message);
        }
        */

        return $this->mediaTypeIdentifiers[$type] ?? null;
    }

    /**
     * Extract a more precise zipped media type when possible.
     *
     * In many cases, the media type is saved in a uncompressed file "mimetype"
     * at the beginning of the zip file. If present, get it.
     *
     * @return string
     */
    protected function getMediaTypeZip()
    {
        $filepath = $this->getTempPath();
        $handle = fopen($filepath, 'rb');
        $contents = fread($handle, 256);
        fclose($handle);
        return substr($contents, 30, 8) === 'mimetype'
            ? substr($contents, 38, strpos($contents, 'PK', 38) - 38)
            : null;
    }
}
