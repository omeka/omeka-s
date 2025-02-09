<?php
namespace Omeka\Module\Exception;

/**
 * Throw this exception in your Module's install method if preconditions for
 * installation are not met.
 */
class ModuleCannotInstallException extends \RuntimeException implements ExceptionInterface
{
}
