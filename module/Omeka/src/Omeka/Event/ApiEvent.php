<?php
namespace Omeka\Event;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Zend\EventManager\Event;

/**
 * API event.
 */
class ApiEvent extends Event
{
    const EVENT_EXECUTE_PRE  = 'execute.pre';
    const EVENT_EXECUTE_POST = 'execute.post';
    const EVENT_SEARCH_PRE   = 'search.pre';
    const EVENT_SEARCH_POST  = 'search.post';
    const EVENT_SEARCH_QUERY = 'search.query';
    const EVENT_CREATE_PRE   = 'create.pre';
    const EVENT_CREATE_POST  = 'create.post';
    const EVENT_READ_PRE     = 'read.pre';
    const EVENT_READ_POST    = 'read.post';
    const EVENT_UPDATE_PRE   = 'update.pre';
    const EVENT_UPDATE_POST  = 'update.post';
    const EVENT_DELETE_PRE   = 'delete.pre';
    const EVENT_DELETE_POST  = 'delete.post';
}
