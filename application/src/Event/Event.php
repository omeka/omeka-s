<?php
namespace Omeka\Event;

use Zend\EventManager\Event as ZendEvent;

/**
 * An Omeka event
 */
class Event extends ZendEvent
{
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
    const API_SEARCH_QUERY = 'api.search.query';
    const API_FIND_QUERY   = 'api.find.query';
    const API_FIND_POST    = 'api.find.post';
    const API_HYDRATE_PRE  = 'api.hydrate.pre';
    const API_HYDRATE_POST = 'api.hydrate.post';
     /**#@-*/

    // API JSON-LD context definitions event
     const API_CONTEXT = 'api.context';

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
     * Events triggered by API representations.
     *
     * All classes that extend {@link Omeka\Api\Representation\AbstractRepresentation}
     * trigger these events.
     */
    const REP_RESOURCE_JSON = 'rep.resource.json';
    const REP_VALUE_HTML = 'rep.value.html';

    /**#@+
     * Events triggered by views.
     *
     * Use the view's controller name as the event identifier.
     */
    const VIEW_LAYOUT       = 'view.layout';
    const VIEW_SHOW_AFTER   = 'view.show.after';
    const VIEW_BROWSE_AFTER = 'view.browse.after';
    const VIEW_ADD_AFTER    = 'view.add.after';
    const VIEW_EDIT_AFTER   = 'view.edit.after';
    const VIEW_ADD_FORM_AFTER   = 'view.add.form.after';
    const VIEW_EDIT_FORM_AFTER  = 'view.edit.form.after';
    const VIEW_SHOW_SECTION_NAV = 'view.show.section_nav';
    const VIEW_ADD_SECTION_NAV  = 'view.add.section_nav';
    const VIEW_EDIT_SECTION_NAV = 'view.edit.section_nav';
    const VIEW_ADVANCED_SEARCH = 'view.advanced_search';

    /**
     * Event triggered by service managers.
     *
     * All classes that extend {@link Omeka\ServiceManager\AbstractPluginManager}
     * trigger this event.
     */
    const SERVICE_REGISTERED_NAMES = 'service.registered_names';

    // Site settings event
    const SITE_SETTINGS_ADD_ELEMENTS = 'site_settings.add_elements';
    const SITE_SETTINGS_ADD_INPUT_FILTERS = 'site_settings.add_input_filters';

    // Global settings event
    const GLOBAL_SETTINGS_ADD_ELEMENTS = 'global_settings.add_elements';
    const GLOBAL_SETTINGS_ADD_INPUT_FILTERS = 'global_settings.add_input_filters';

    // Resource visibility SQL filter event
    const SQL_FILTER_RESOURCE_VISIBILITY = 'sql_filter.resource_visibility';
}
