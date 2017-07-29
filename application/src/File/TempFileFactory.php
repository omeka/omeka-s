<?php
namespace Omeka\File;

class TempFileFactory
{
    protected $tempDir;
    protected $fileManagerConfig;
    protected $mediaTypeMap;
    protected $fileManager;

    public function __construct($tempDir, array $config, array $mediaTypeMap, Manager $fileManager)
    {
        $this->tempDir = $tempDir;
        $this->config = $config;
        $this->mediaTypeMap = $mediaTypeMap;
        $this->fileManager = $fileManager;
    }

    public function create()
    {
        return new TempFile(
            $this->tempDir,
            $this->config,
            $this->mediaTypeMap,
            $this->fileManager
        );
    }
}
