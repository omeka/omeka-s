<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool\ConstructorParameterResolver;

final class FallbackConstructorParameter
{
    public function __construct(
        public mixed $argumentValue,
    ) {
    }
}
