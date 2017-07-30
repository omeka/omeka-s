<?php
namespace Omeka\File;

use Omeka\File\Store\StoreInterface;

class TempFileFactory
{
    protected $tempDir;
    protected $fileManagerConfig;
    protected $mediaTypeMap;
    protected $store;
    protected $fileManager;

    public function __construct($tempDir, array $config, array $mediaTypeMap,
        StoreInterface $store, Manager $fileManager
    ) {
        $this->tempDir = $tempDir;
        $this->config = $config;
        $this->mediaTypeMap = $mediaTypeMap;
        $this->store = $store;
        $this->fileManager = $fileManager;
    }

    public function create()
    {
        return new TempFile(
            $this->tempDir,
            $this->config,
            $this->mediaTypeMap,
            $this->store,
            $this->fileManager
        );
    }
}
