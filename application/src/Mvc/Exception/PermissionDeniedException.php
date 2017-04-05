<?php
namespace Omeka\Mvc\Exception;

use Omeka\Permissions\Exception\PermissionDeniedException as AclPermissionDeniedException;

class PermissionDeniedException extends AclPermissionDeniedException implements ExceptionInterface
{
}
