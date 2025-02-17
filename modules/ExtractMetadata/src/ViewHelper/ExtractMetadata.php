<?php
namespace ExtractMetadata\ViewHelper;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\AbstractHelper;

class ExtractMetadata extends AbstractHelper
{
    protected $services;
    protected $extractorManager;
    protected $mapperManager;

    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
        $this->extractorManager = $this->services->get('ExtractMetadata\ExtractorManager');
        $this->mapperManager = $this->services->get('ExtractMetadata\MapperManager');
    }

    public function getExtractor($extractorName)
    {
        return $this->extractorManager->get($extractorName);
    }

    public function getMapper($mapperName)
    {
        return $this->mapperManager->get($mapperName);
    }
}
