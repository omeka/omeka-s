<?php

declare(strict_types=1);

namespace LinkedDataSets\Infrastructure\Services\Factories;

use Laminas\ServiceManager\Factory\FactoryInterface;
use LinkedDataSets\Application\Service\ItemSetCrawler;
use Psr\Container\ContainerInterface;

final class ItemSetCrawlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ItemSetCrawler
    {
        return new ItemSetCrawler($container);
    }
}
