<?php
namespace Omeka\Event;

use Zend\EventManager\Event;

/**
 * API event.
 */
class ApiEvent extends Event
{
    /**#@+
     * API events triggered by the API manger.
     */
    const EVENT_EXECUTE_PRE       = 'execute.pre';
    const EVENT_EXECUTE_POST      = 'execute.post';
    const EVENT_SEARCH_PRE        = 'search.pre';
    const EVENT_SEARCH_POST       = 'search.post';
    const EVENT_SEARCH_QUERY      = 'search.query';
    const EVENT_CREATE_PRE        = 'create.pre';
    const EVENT_CREATE_POST       = 'create.post';
    const EVENT_BATCH_CREATE_PRE  = 'batch_create.pre';
    const EVENT_BATCH_CREATE_POST = 'batch_create.post';
    const EVENT_READ_PRE          = 'read.pre';
    const EVENT_READ_POST         = 'read.post';
    const EVENT_UPDATE_PRE        = 'update.pre';
    const EVENT_UPDATE_POST       = 'update.post';
    const EVENT_DELETE_PRE        = 'delete.pre';
    const EVENT_DELETE_POST       = 'delete.post';
    /**#@-*/

    /**#@+
     * API events triggered by API entity adapters.
     */
    const EVENT_CREATE_VALIDATE_PRE = 'create.validate.pre';
    const EVENT_READ_FIND_POST      = 'read.find.post';
    const EVENT_UPDATE_VALIDATE_PRE = 'update.validate.pre';
    const EVENT_DELETE_FIND_POST    = 'delete.find.post';
     /**#@-*/
}
