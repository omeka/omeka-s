<?php

declare(strict_types=1);

namespace LinkedDataSets\Application\Dto;

final class DistributionDto
{
    private string $format;
    private string $fileName;
    private int $id;
    public function __construct(string $format, string $fileName, int $id)
    {
        $this->fileName = $fileName;
        $this->format = $format;
        $this->id = $id;

        if (empty($this->format) || empty($this->fileName)) {
            throw new \Exception('Filename or format is unknown');
        }
    }

    public function getFilename(): string
    {
        return $this->fileName;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
