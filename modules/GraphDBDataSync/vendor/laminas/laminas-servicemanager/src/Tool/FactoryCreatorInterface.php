<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool;

interface FactoryCreatorInterface
{
    /**
     * @param class-string $className
     * @param array<string,string> $aliases
     * @return non-empty-string
     */
    public function createFactory(string $className, array $aliases = []): string;
}
