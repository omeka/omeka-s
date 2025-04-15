<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool\ConstructorParameterResolver;

final class ServiceFromContainerConstructorParameter
{
    /**
     * @param non-empty-string $serviceName
     */
    public function __construct(
        public string $serviceName,
    ) {
    }
}
