<?php

declare(strict_types=1);

namespace Laminas\ServiceManager;

final class Module
{
    public function getConfig(): array
    {
        $provider                  = new ConfigProvider();
        $config                    = $provider();
        $config['service_manager'] = $config['dependencies'];
        unset($config['dependencies']);
        return $config;
    }
}
