<?php

namespace Omeka\Http\Client\Exception;

use Laminas\Http\Client\Exception\RuntimeException as LaminasException;

class RuntimeException extends LaminasException implements ExceptionInterface
{
}
