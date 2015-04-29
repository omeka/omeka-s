<?php
namespace Omeka\Event;

use Zend\EventManager\Event as ZendEvent;

/**
 * An Omeka event
 */
class Event extends ZendEvent
{
    /**#@+
     * Events triggered by Omeka modules.
     */
    const MODULE_INSTALL    = 'module.install';
    const MODULE_UNINSTALL  = 'module.uninstall';
    const MODULE_ACTIVATE   = 'module.activate';
    const MODULE_DEACTIVATE = 'module.deactivate';
    const MODULE_UPGRADE    = 'module.upgrade';
     /**#@-*/

    /**#@+
     * Events triggered by all API adapters.
     *
     * All classes that extend {@link Omeka\Api\Adapter\AbstractAdapter} trigger
     * these events.
     */
    const API_EXECUTE_PRE       = 'api.execute.pre';
    const API_EXECUTE_POST      = 'api.execute.post';
    const API_SEARCH_PRE        = 'api.search.pre';
    const API_SEARCH_POST       = 'api.search.post';
    const API_CREATE_PRE        = 'api.create.pre';
    const API_CREATE_POST       = 'api.create.post';
    const API_BATCH_CREATE_PRE  = 'api.batch_create.pre';
    const API_BATCH_CREATE_POST = 'api.batch_create.post';
    const API_READ_PRE          = 'api.read.pre';
    const API_READ_POST         = 'api.read.post';
    const API_UPDATE_PRE        = 'api.update.pre';
    const API_UPDATE_POST       = 'api.update.post';
    const API_DELETE_PRE        = 'api.delete.pre';
    const API_DELETE_POST       = 'api.delete.post';
    /**#@-*/

    /**#@+
     * Events triggered by API entity adapters.
     *
     * All classes that extend {@link Omeka\Api\Adapter\AbstractEntityAdapter}
     * trigger these events.
     */
    const API_SEARCH_QUERY        = 'api.search.query';
    const API_VALIDATE_DATA_PRE   = 'api.validate.data.pre';
    const API_VALIDATE_ENTITY_PRE = 'api.validate.entity.pre';
    const API_READ_FIND_POST      = 'api.read.find.post';
    const API_DELETE_FIND_POST    = 'api.delete.find.post';
     /**#@-*/

    /**#@+
     * Events triggered by Doctrine lifecycle events.
     */
    const ENTITY_REMOVE_PRE   = 'entity.remove.pre';
    const ENTITY_REMOVE_POST  = 'entity.remove.post';
    const ENTITY_PERSIST_PRE  = 'entity.persist.pre';
    const ENTITY_PERSIST_POST = 'entity.persist.post';
    const ENTITY_UPDATE_PRE   = 'entity.update.pre';
    const ENTITY_UPDATE_POST  = 'entity.update.post';
    /**#@-*/

    /**#@+
     * Events triggered by views.
     *
     * Use the view's controller name as the event identifier.
     */
    const VIEW_LAYOUT       = 'view.layout';
    const VIEW_SHOW_AFTER   = 'view.show.after';
    const VIEW_BROWSE_AFTER = 'view.browse.after';

    // ACL event
    const ACL = 'acl';
}
