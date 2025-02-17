<?php declare(strict_types=1);

namespace Common\File;

use Common\Mvc\Controller\Plugin\SpecifyMediaType;
use Laminas\EventManager\EventManagerAwareTrait;

class TempFileFactory extends \Omeka\File\TempFileFactory
{
    use EventManagerAwareTrait;

    /**
     * @var \Common\Mvc\Controller\Plugin\SpecifyMediaType
     */
    protected $specifyMediaType;

    public function build()
    {
        $tempFile = new TempFile($this->tempDir, $this->mediaTypeMap,
            $this->store, $this->thumbnailManager, $this->validator
        );
        $tempFile->setEventManager($this->getEventManager());
        return $tempFile
            ->setSpecifyMediaType($this->specifyMediaType);
    }

    public function setSpecifyMediaType(SpecifyMediaType $specifyMediaType): self
    {
        $this->specifyMediaType = $specifyMediaType;
        return $this;
    }
}
