<?php
namespace Omeka\Event;

use Zend\EventManager\Event as ZendEvent;

/**
 * An Omeka event
 */
class Event extends ZendEvent
{
    /**#@+
     * Events triggered by the Omeka\ApiManager service.
     */
    const EVENT_EXECUTE_PRE  = 'execute.pre';
    const EVENT_EXECUTE_POST = 'execute.post';
    /**#@-*/

    /**#@+
     * Events triggered by API adapters.
     *
     * All classes that extend {@link Omeka\Api\Adapter\AbstractAdapter}
     * trigger these events.
     */
    const EVENT_SEARCH_PRE        = 'search.pre';
    const EVENT_SEARCH_POST       = 'search.post';
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
     * Events triggered by API entity adapters.
     *
     * All classes that extend {@link Omeka\Api\Adapter\Entity\AbstractEntityAdapter}
     * trigger these events.
     */
    const EVENT_SEARCH_QUERY        = 'search.query';
    const EVENT_CREATE_VALIDATE_PRE = 'create.validate.pre';
    const EVENT_READ_FIND_POST      = 'read.find.post';
    const EVENT_UPDATE_VALIDATE_PRE = 'update.validate.pre';
    const EVENT_DELETE_FIND_POST    = 'delete.find.post';
     /**#@-*/

    /**#@+
     * Events triggered by the Omeka\Acl service factory.
     */
    const EVENT_ACL = 'acl';
     /**#@-*/

    /**#@+
     * Events triggered by the Omeka\ModuleManager service.
     */
    const EVENT_MODULE_INSTALL    = 'module.install';
    const EVENT_MODULE_UNINSTALL  = 'module.uninstall';
    const EVENT_MODULE_ACTIVATE   = 'module.activate';
    const EVENT_MODULE_DEACTIVATE = 'module.deactivate';
    const EVENT_MODULE_UPGRADE    = 'module.upgrade';
     /**#@-*/
}
