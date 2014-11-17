<?php

namespace Omeka\Db\Migration\Exception;

/**
 * Exception thrown when down() is called on a migration that cannot be
 * downgraded.
 */
class DowngradeUnsupportedException extends \RuntimeException implements ExceptionInterface
{
}
