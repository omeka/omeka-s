<?php
namespace Omeka\File;

class TempFileFactory
{
    protected $fileManager;

    public function __construct(Manager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function create()
    {
        return new TempFile($this->fileManager);
    }
}
