<?php
namespace Omeka\File;

class Manager
{
    /**
     * The default media type whitelist.
     */
    const MEDIA_TYPE_WHITELIST = [
        // application/*
        'application/msword',
        'application/ogg',
        'application/pdf',
        'application/rtf',
        'application/vnd.ms-access',
        'application/vnd.ms-excel',
        'application/vnd.ms-powerpoint',
        'application/vnd.ms-project',
        'application/vnd.ms-write',
        'application/vnd.oasis.opendocument.chart',
        'application/vnd.oasis.opendocument.database',
        'application/vnd.oasis.opendocument.formula',
        'application/vnd.oasis.opendocument.graphics',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.text',
        'application/x-gzip',
        'application/x-ms-wmp',
        'application/x-msdownload',
        'application/x-shockwave-flash',
        'application/x-tar',
        'application/zip',
        // audio/*
        'audio/midi',
        'audio/mp4',
        'audio/mpeg',
        'audio/ogg',
        'audio/x-aac',
        'audio/x-aiff',
        'audio/x-ms-wma',
        'audio/x-ms-wax',
        'audio/x-realaudio',
        'audio/x-wav',
        // image/*
        'image/bmp',
        'image/gif',
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/tiff',
        'image/x-icon',
        // text/*
        'text/css',
        'text/plain',
        'text/richtext',
        // video/*
        'video/divx',
        'video/mp4',
        'video/mpeg',
        'video/ogg',
        'video/quicktime',
        'video/webm',
        'video/x-ms-asf,',
        'video/x-msvideo',
        'video/x-ms-wmv',
    ];

    const EXTENSION_WHITELIST = [
        'aac', 'aif', 'aiff', 'asf', 'asx', 'avi', 'bmp', 'c', 'cc', 'class',
        'css', 'divx', 'doc', 'docx', 'exe', 'gif', 'gz', 'gzip', 'h', 'ico',
        'j2k', 'jp2', 'jpe', 'jpeg', 'jpg', 'm4a', 'm4v', 'mdb', 'mid', 'midi', 'mov',
        'mp2', 'mp3', 'mp4', 'mpa', 'mpe', 'mpeg', 'mpg', 'mpp', 'odb', 'odc',
        'odf', 'odg', 'odp', 'ods', 'odt', 'ogg', 'opus', 'pdf', 'png', 'pot', 'pps',
        'ppt', 'pptx', 'qt', 'ra', 'ram', 'rtf', 'rtx', 'swf', 'tar', 'tif',
        'tiff', 'txt', 'wav', 'wax', 'webm', 'wma', 'wmv', 'wmx', 'wri', 'xla', 'xls',
        'xlsx', 'xlt', 'xlw', 'zip',
    ];

    /**
     * @var array
     */
    protected $config;

    /**
     * Set configuration during construction.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get all thumbnail types.
     *
     * @return array
     */
    public function getThumbnailTypes()
    {
        return array_keys($this->config['thumbnail_types']);
    }
}
