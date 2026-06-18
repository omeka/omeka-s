<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function __invoke(): array
    {
        return $this->config;
    }
}
