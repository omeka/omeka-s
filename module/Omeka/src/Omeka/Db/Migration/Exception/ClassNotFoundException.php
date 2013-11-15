<?php

namespace Omeka\Db\Migration\Exception;

/**
 * Exception thrown when the expected class is not present in a migration
 * file.
 */
class ClassNotFoundException extends \LogicException implements ExceptionInterface
{
}
