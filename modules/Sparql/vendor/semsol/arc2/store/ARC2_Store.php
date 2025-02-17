<?php
/**
 * ARC2 RDF Store.
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 */

use ARC2\Store\Adapter\AbstractAdapter;
use ARC2\Store\Adapter\AdapterFactory;
use ARC2\Store\TableManager\SQLite;

ARC2::inc('Class');

#[AllowDynamicProperties]
class ARC2_Store extends ARC2_Class
{
    public $cache;
    public string $column_type;
    public $db;
    public string $db_version;
    public int $has_fulltext_index;
    public $is_win;
    public int $max_split_tables;
    public int $queue_queries;

    /**
     * @var array<string>
     */
    public array $resource_labels;

    /**
     * @var array<mixed>
     */
    public array $split_predicates;
    public int $table_lock;
    public string $tbl_prefix;

    /**
     * @var array<mixed>
     */
    public array $term_id_cache;

    /**
     * @var array<mixed>
     */
    public array $triggers;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->table_lock = 0;
        $this->triggers = $this->v('store_triggers', [], $this->a);
        $this->queue_queries = $this->v('store_queue_queries', 0, $this->a);
        $this->is_win = ('win' == strtolower(substr(\PHP_OS, 0, 3))) ? true : false;
        $this->max_split_tables = $this->v('store_max_split_tables', 10, $this->a);
        $this->split_predicates = $this->v('store_split_predicates', [], $this->a);

        /*
         * setup cache instance, if required by the user.
         */
        if ($this->cacheEnabled()) {
            // reuse existing cache instance, if it implements Psr\SimpleCache\CacheInterface
            if (isset($this->a['cache_instance'])
                && $this->a['cache_instance'] instanceof \Psr\SimpleCache\CacheInterface) {
                $this->cache = $this->a['cache_instance'];

                // create new cache instance
            } else {
                // FYI: https://symfony.com/doc/current/components/cache/adapters/filesystem_adapter.html
                $this->cache = new \Symfony\Component\Cache\Simple\FilesystemCache('arc2', 0, null);
            }
        }
    }

    public function cacheEnabled()
    {
        return isset($this->a['cache_enabled'])
            && true === $this->a['cache_enabled']
            && 'pdo' == $this->a['db_adapter'];
    }

    public function getName()
    {
        return $this->v('store_name', 'arc', $this->a);
    }

    public function getTablePrefix()
    {
        if (!isset($this->tbl_prefix)) {
            $r = $this->v('db_table_prefix', '', $this->a);
            $r .= $r ? '_' : '';
            $r .= $this->getName().'_';
            $this->tbl_prefix = $r;
        }

        return $this->tbl_prefix;
    }

    public function createDBCon()
    {
        // build connection credential array
        $credentArr = ['db_host' => 'localhost', 'db_user' => '', 'db_pwd' => '', 'db_name' => ''];
        foreach ($credentArr as $k => $v) {
            $this->a[$k] = $this->v($k, $v, $this->a);
        }

        // connect
        try {
            if (false === class_exists(AdapterFactory::class)) {
                require __DIR__.'/../src/ARC2/Store/Adapter/AdapterFactory.php';
            }
            if (false == isset($this->a['db_adapter'])) {
                $this->a['db_adapter'] = 'pdo';
                $this->a['db_pdo_protocol'] = 'mysql';
            }
            $factory = new AdapterFactory();
            $this->db = $factory->getInstanceFor($this->a['db_adapter'], $this->a);
            $err = $this->db->connect();
            // stop here, if an error occoured
            if (is_string($err) && false !== empty($err)) {
                throw new Exception($err);
            }
        } catch (Exception $e) {
            return $this->addError($e->getMessage());
        }

        $this->a['db_object'] = $this->db;

        return true;
    }

    public function getDBObject(): ?AbstractAdapter
    {
        return $this->db;
    }

    /**
     * @param int $force 1 if you want to force a connection
     */
    public function getDBCon($force = 0): bool
    {
        if ($force || !isset($this->a['db_object'])) {
            if (false === $this->createDBCon()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @todo make property $a private, but provide access via a getter
     */
    public function closeDBCon()
    {
        if (isset($this->a['db_object'])) {
            $this->db->disconnect();
        }
        unset($this->a['db_con']);
        unset($this->a['db_object']);
    }

    public function getDBVersion()
    {
        if (!$this->v('db_version')) {
            // connect, if no connection available
            if (null == $this->db) {
                $this->createDBCon();
            }

            $this->db_version = $this->db->getServerVersion();
        }

        return $this->db_version;
    }

    /**
     * @return string Returns DBS name. Possible values: mysql, mariadb
     */
    public function getDBSName()
    {
        return $this->db->getDBSName();
    }

    public function getCollation()
    {
        $row = $this->db->fetchRow('SHOW TABLE STATUS LIKE "'.$this->getTablePrefix().'setting"');

        return isset($row['Collation']) ? $row['Collation'] : '';
    }

    public function getColumnType()
    {
        if (!$this->v('column_type')) {
            // MySQL
            $tbl = $this->getTablePrefix().'g2t';

            $row = $this->db->fetchRow('SHOW COLUMNS FROM '.$tbl.' LIKE "t"');
            if (null == $row) {
                $row = ['Type' => 'mediumint'];
            }

            $this->column_type = preg_match('/mediumint/', $row['Type']) ? 'mediumint' : 'int';
        }

        return $this->column_type;
    }

    public function hasHashColumn($tbl)
    {
        $var_name = 'has_hash_column_'.$tbl;
        if (!isset($this->$var_name)) {
            $tbl = $this->getTablePrefix().$tbl;

            // only check if SQLite is NOT being used
            $row = $this->db->fetchRow('SHOW COLUMNS FROM '.$tbl.' LIKE "val_hash"');

            // fix: $row doesn't return 'null'
            $this->$var_name = null;
            if ($row) {
                $this->$var_name = true;
            }
        }

        return $this->$var_name;
    }

    public function hasFulltextIndex()
    {
        if (!isset($this->has_fulltext_index)) {
            $this->has_fulltext_index = 0;
            $tbl = $this->getTablePrefix().'o2val';

            $rows = $this->db->fetchList('SHOW INDEX FROM '.$tbl);
            foreach ($rows as $row) {
                if ('val' != $row['Column_name']) {
                    continue;
                }
                if ('FULLTEXT' != $row['Index_type']) {
                    continue;
                }
                $this->has_fulltext_index = 1;
                break;
            }
        }

        return $this->has_fulltext_index;
    }

    public function enableFulltextSearch()
    {
        if ($this->hasFulltextIndex()) {
            return 1;
        }
        $tbl = $this->getTablePrefix().'o2val';
        $this->db->simpleQuery('CREATE FULLTEXT INDEX vft ON '.$tbl.'(val(128))');
    }

    public function disableFulltextSearch()
    {
        if (!$this->hasFulltextIndex()) {
            return 1;
        }
        $tbl = $this->getTablePrefix().'o2val';
        $this->db->simpleQuery('DROP INDEX vft ON '.$tbl);
    }

    /**
     * @todo required?
     *
     * @return int real process amount when using MySQL, 1 otherwise
     */
    public function countDBProcesses(): int
    {
        return $this->db->getNumberOfRows('SHOW PROCESSLIST');
    }

    /**
     * Manipulating database processes using ARC2 is discouraged.
     *
     * @deprecated
     */
    public function killDBProcesses($needle = '', $runtime = 30)
    {
        /* make sure needle is sql */
        if (preg_match('/\?.+ WHERE/i', $needle, $m)) {
            $needle = $this->query($needle, 'sql');
        }
        $ref_tbl = $this->getTablePrefix().'triple';

        $rows = $this->db->fetchList('SHOW FULL PROCESSLIST');
        foreach ($rows as $row) {
            if ($row['Time'] < $runtime) {
                continue;
            }
            if (!preg_match('/^\s*(INSERT|SELECT) /s', $row['Info'])) {
                continue;
            } /* only basic queries */
            if (!strpos($row['Info'], $ref_tbl.' ')) {
                continue;
            } /* only from this store */
            $kill = 0;
            if ($needle && str_contains($row['Info'], $needle)) {
                $kill = 1;
            }
            if (!$needle) {
                $kill = 1;
            }
            if (!$kill) {
                continue;
            }
            $this->db->simpleQuery('KILL '.$row['Id']);
        }
    }

    public function getTables()
    {
        return ['triple', 'g2t', 'id2val', 's2val', 'o2val', 'setting'];
    }

    public function isSetUp()
    {
        if (null !== $this->db) {
            $tbl = $this->getTablePrefix().'setting';

            try {
                // mysqli way
                $this->db->fetchRow('SELECT 1 FROM '.$tbl.' LIMIT 1');

                return true;
            } catch (\Exception $e) {
                // when using PDO, an exception gets thrown if $tbl does not exist.
            }
        }

        return false;
    }

    public function setUp($force = 0)
    {
        if (($force || !$this->isSetUp()) && false !== $this->getDBCon()) {
            // default way
            ARC2::inc('StoreTableManager');
            (new ARC2_StoreTableManager($this->a, $this))->createTables();
        }
    }

    public function extendColumns()
    {
        ARC2::inc('StoreTableManager');
        $mgr = new ARC2_StoreTableManager($this->a, $this);
        $mgr->extendColumns();
        $this->column_type = 'int';
    }

    public function splitTables()
    {
        ARC2::inc('StoreTableManager');
        $mgr = new ARC2_StoreTableManager($this->a, $this);
        $mgr->splitTables();
    }

    public function hasSetting($k)
    {
        if (null == $this->db) {
            $this->createDBCon();
        }

        $tbl = $this->getTablePrefix().'setting';

        return $this->db->fetchRow('SELECT val FROM '.$tbl." WHERE k = '".md5($k)."'")
            ? 1
            : 0;
    }

    public function getSetting($k, $default = 0)
    {
        if (null == $this->db) {
            $this->createDBCon();
        }

        $tbl = $this->getTablePrefix().'setting';
        $row = $this->db->fetchRow('SELECT val FROM '.$tbl." WHERE k = '".md5($k)."'");
        if (isset($row['val'])) {
            return unserialize($row['val']);
        }

        return $default;
    }

    public function setSetting($k, $v)
    {
        $tbl = $this->getTablePrefix().'setting';
        if ($this->hasSetting($k)) {
            $sql = 'UPDATE '.$tbl." SET val = '".$this->db->escape(serialize($v))."' WHERE k = '".md5($k)."'";
        } else {
            $sql = 'INSERT INTO '.$tbl." (k, val) VALUES ('".md5($k)."', '".$this->db->escape(serialize($v))."')";
        }

        return $this->db->simpleQuery($sql);
    }

    public function removeSetting($k)
    {
        $tbl = $this->getTablePrefix().'setting';

        return $this->db->simpleQuery('DELETE FROM '.$tbl." WHERE k = '".md5($k)."'");
    }

    public function getQueueTicket()
    {
        if (!$this->queue_queries) {
            return 1;
        }
        $t = 'ticket_'.substr(md5(uniqid(rand())), 0, 10);
        /* lock */
        $this->db->simpleQuery('LOCK TABLES '.$this->getTablePrefix().'setting WRITE');
        /* queue */
        $queue = $this->getSetting('query_queue', []);
        $queue[] = $t;
        $this->setSetting('query_queue', $queue);
        $this->db->simpleQuery('UNLOCK TABLES');
        /* loop */
        $lc = 0;
        $queue = $this->getSetting('query_queue', []);
        while ($queue && ($queue[0] != $t) && ($lc < 30)) {
            if ($this->is_win) {
                sleep(1);
                ++$lc;
            } else {
                usleep(100000);
                $lc += 0.1;
            }
            $queue = $this->getSetting('query_queue', []);
        }

        return ($lc < 30) ? $t : 0;
    }

    public function removeQueueTicket($t)
    {
        if (!$this->queue_queries) {
            return 1;
        }
        /* lock */
        $this->db->simpleQuery('LOCK TABLES '.$this->getTablePrefix().'setting WRITE');
        /* queue */
        $vals = $this->getSetting('query_queue', []);
        $pos = array_search($t, $vals);
        $queue = ($pos < (count($vals) - 1)) ? array_slice($vals, $pos + 1) : [];
        $this->setSetting('query_queue', $queue);
        $this->db->simpleQuery('UNLOCK TABLES');
    }

    public function reset($keep_settings = 0)
    {
        $tbls = $this->getTables();
        $prefix = $this->getTablePrefix();
        /* remove split tables */
        $ps = $this->getSetting('split_predicates', []);
        foreach ($ps as $p) {
            $tbl = 'triple_'.abs(crc32($p));
            $this->db->simpleQuery('DROP TABLE '.$prefix.$tbl);
        }
        $this->removeSetting('split_predicates');
        /* truncate tables */
        foreach ($tbls as $tbl) {
            if ($keep_settings && ('setting' == $tbl)) {
                continue;
            }
            $this->db->simpleQuery('TRUNCATE '.$prefix.$tbl);
        }
    }

    public function drop()
    {
        if (null == $this->db) {
            $this->createDBCon();
        }

        $prefix = $this->getTablePrefix();
        $tbls = $this->getTables();
        foreach ($tbls as $tbl) {
            $this->db->simpleQuery('DROP TABLE IF EXISTS '.$prefix.$tbl);
        }
    }

    public function insert($doc, $g, $keep_bnode_ids = 0)
    {
        $doc = is_array($doc) ? $this->toTurtle($doc) : $doc;
        $infos = ['query' => ['url' => $g, 'target_graph' => $g]];
        ARC2::inc('StoreLoadQueryHandler');
        $h = new ARC2_StoreLoadQueryHandler($this->a, $this);
        $r = $h->runQuery($infos, $doc, $keep_bnode_ids);
        $this->processTriggers('insert', $infos);

        return $r;
    }

    public function delete($doc, $g)
    {
        if (!$doc) {
            $infos = ['query' => ['target_graphs' => [$g]]];
            ARC2::inc('StoreDeleteQueryHandler');
            $h = new ARC2_StoreDeleteQueryHandler($this->a, $this);
            $r = $h->runQuery($infos);
            $this->processTriggers('delete', $infos);

            return $r;
        }
    }

    public function replace($doc, $g, $doc_2)
    {
        return [$this->delete($doc, $g), $this->insert($doc_2, $g)];
    }

    public function dump()
    {
        ARC2::inc('StoreDumper');
        $d = new ARC2_StoreDumper($this->a, $this);
        $d->dumpSPOG();
    }

    public function createBackup($path, $q = '')
    {
        ARC2::inc('StoreDumper');
        $d = new ARC2_StoreDumper($this->a, $this);
        $d->saveSPOG($path, $q);
    }

    public function renameTo($name)
    {
        $tbls = $this->getTables();
        $old_prefix = $this->getTablePrefix();
        $new_prefix = $this->v('db_table_prefix', '', $this->a);
        $new_prefix .= $new_prefix ? '_' : '';
        $new_prefix .= $name.'_';
        foreach ($tbls as $tbl) {
            $sql = 'RENAME TABLE '.$old_prefix.$tbl.' TO '.$new_prefix.$tbl;

            $this->db->simpleQuery($sql);
            if (!empty($this->db->getErrorMessage())) {
                return $this->addError($this->db->getErrorMessage());
            }
        }
        $this->a['store_name'] = $name;
        unset($this->tbl_prefix);
    }

    public function replicateTo($name)
    {
        $conf = array_merge($this->a, ['store_name' => $name]);
        $new_store = ARC2::getStore($conf);
        $new_store->setUp();
        $new_store->reset();
        $tbls = $this->getTables();
        $old_prefix = $this->getTablePrefix();
        $new_prefix = $new_store->getTablePrefix();

        $sqlHead = 'INSERT IGNORE INTO ';

        foreach ($tbls as $tbl) {
            $this->db->simpleQuery($sqlHead.$new_prefix.$tbl.' SELECT * FROM '.$old_prefix.$tbl);
            if (!empty($this->db->getErrorMessage())) {
                return $this->addError($this->db->getErrorMessage());
            }
        }

        return $new_store->query('SELECT COUNT(*) AS t_count WHERE { ?s ?p ?o}', 'row');
    }

    /**
     * Executes a SPARQL query.
     *
     * @param string $q              SPARQL query
     * @param string $result_format  Possible values: infos, raw, rows, row
     * @param string $src
     * @param int    $keep_bnode_ids Keep blank node IDs? Default is 0
     * @param int    $log_query      Log executed queries? Default is 0
     *
     * @return array|int array if query returned a result, 0 otherwise
     */
    public function query($q, $result_format = '', $src = '', $keep_bnode_ids = 0, $log_query = 0)
    {
        if ($log_query) {
            $this->logQuery($q);
        }
        if (preg_match('/^dump/i', $q)) {
            $infos = ['query' => ['type' => 'dump']];
        } else {
            // check cache
            $key = hash('sha1', $q);
            if ($this->cacheEnabled() && $this->cache->has($key.'_infos')) {
                $infos = $this->cache->get($key.'_infos');
                $errors = $this->cache->get($key.'_errors');
                // no entry found
            } else {
                ARC2::inc('SPARQLPlusParser');
                $p = new ARC2_SPARQLPlusParser($this->a, $this);
                $p->parse($q, $src);
                $infos = $p->getQueryInfos();
                $errors = $p->getErrors();

                // store result in cache
                if ($this->cacheEnabled()) {
                    $this->cache->set($key.'_infos', $infos);
                    $this->cache->set($key.'_errors', $errors);
                }
            }
        }

        if ('infos' == $result_format) {
            return $infos;
        }

        $infos['result_format'] = $result_format;

        if (!isset($p) || 0 == count($errors)) {
            $qt = $infos['query']['type'];
            if (!in_array($qt, ['select', 'ask', 'describe', 'construct', 'load', 'insert', 'delete', 'dump'])) {
                return $this->addError('Unsupported query type "'.$qt.'"');
            }
            $t1 = ARC2::mtime();

            // if cache is enabled, get/store result
            $key = hash('sha1', $q);
            if ($this->cacheEnabled() && $this->cache->has($key)) {
                $result = $this->cache->get($key);
            } else {
                $result = $this->runQuery($infos, $qt, $keep_bnode_ids, $q);

                // store in cache, if enabled
                if ($this->cacheEnabled()) {
                    $this->cache->set($key, $result);
                }
            }

            $r = ['query_type' => $qt, 'result' => $result];
            $r['query_time'] = ARC2::mtime() - $t1;

            /* query result */
            if ('raw' == $result_format) {
                return $r['result'];
            }
            if ('rows' == $result_format) {
                return $r['result']['rows'] ? $r['result']['rows'] : [];
            }
            if ('row' == $result_format) {
                return $r['result']['rows'] ? $r['result']['rows'][0] : [];
            }

            return $r;
        }

        return 0;
    }

    /**
     * Runs a SPARQL query. Dont use this function directly, use query instead.
     */
    public function runQuery($infos, $type, $keep_bnode_ids = 0, $q = '')
    {
        // invalidate cache, if enabled and a query is executed, which changes the store
        if ($this->cacheEnabled() && in_array($type, ['load', 'insert', 'delete'])) {
            $this->cache->clear();
        }

        ARC2::inc('Store'.ucfirst($type).'QueryHandler');
        $cls = 'ARC2_Store'.ucfirst($type).'QueryHandler';
        $h = new $cls($this->a, $this);
        $ticket = 1;
        $r = [];
        if ($q && ('select' == $type)) {
            $ticket = $this->getQueueTicket($q);
        }
        if ($ticket) {
            if ('load' == $type) {/* the LoadQH supports raw data as 2nd parameter */
                $r = $h->runQuery($infos, '', $keep_bnode_ids);
            } else {
                $r = $h->runQuery($infos, $keep_bnode_ids);
            }
        }
        if ($q && ('select' == $type)) {
            $this->removeQueueTicket($ticket);
        }
        $trigger_r = $this->processTriggers($type, $infos);

        return $r;
    }

    public function processTriggers($type, $infos)
    {
        $r = [];
        $trigger_defs = $this->triggers;
        $this->triggers = [];
        $triggers = $this->v($type, [], $trigger_defs);
        if ($triggers) {
            $r['trigger_results'] = [];
            $triggers = is_array($triggers) ? $triggers : [$triggers];
            $trigger_inc_path = $this->v('store_triggers_path', '', $this->a);
            foreach ($triggers as $trigger) {
                $trigger .= !preg_match('/Trigger$/', $trigger) ? 'Trigger' : '';
                if (ARC2::inc(ucfirst($trigger), $trigger_inc_path)) {
                    $cls = 'ARC2_'.ucfirst($trigger);
                    $config = array_merge($this->a, ['query_infos' => $infos]);
                    $trigger_obj = new $cls($config, $this);
                    if (method_exists($trigger_obj, 'go')) {
                        $r['trigger_results'][] = $trigger_obj->go();
                    }
                }
            }
        }
        $this->triggers = $trigger_defs;

        return $r;
    }

    public function getValueHash($val, $_32bit = false)
    {
        $hash = crc32($val);
        if ($_32bit && ($hash & 0x80000000)) {
            $hash = sprintf('%u', $hash);
        }
        $hash = abs($hash);

        return $hash;
    }

    public function getTermID($val, $term = '')
    {
        /* mem cache */
        if (!isset($this->term_id_cache) || (count(array_keys($this->term_id_cache)) > 100)) {
            $this->term_id_cache = [];
        }
        if (!isset($this->term_id_cache[$term])) {
            $this->term_id_cache[$term] = [];
        }
        $tbl = preg_match('/^(s|o)$/', $term) ? $term.'2val' : 'id2val';
        /* cached? */
        if ((strlen($val) < 100) && isset($this->term_id_cache[$term][$val])) {
            return $this->term_id_cache[$term][$val];
        }
        $r = 0;
        /* via hash */
        if (preg_match('/^(s2val|o2val)$/', $tbl) && $this->hasHashColumn($tbl)) {
            $rows = $this->db->fetchList(
                'SELECT id, val FROM '.$this->getTablePrefix().$tbl." WHERE val_hash = '".$this->getValueHash($val)."' ORDER BY id"
            );
            if (is_array($rows) && 0 < count($rows)) {
                foreach ($rows as $row) {
                    if ($row['val'] == $val) {
                        $r = $row['id'];
                        break;
                    }
                }
            }
        }
        /* exact match */
        else {
            $sql = 'SELECT id
                    FROM '.$this->getTablePrefix().$tbl."
                    WHERE val = BINARY '".$this->db->escape($val)."'
                    LIMIT 1";

            $row = $this->db->fetchRow($sql);

            if (null !== $row && isset($row['id'])) {
                $r = $row['id'];
            }
        }
        if ($r && (strlen($val) < 100)) {
            $this->term_id_cache[$term][$val] = $r;
        }

        return $r;
    }

    public function getIDValue($id, $term = '')
    {
        $tbl = preg_match('/^(s|o)$/', $term) ? $term.'2val' : 'id2val';
        $row = $this->db->fetchRow(
            'SELECT val FROM '.$this->getTablePrefix().$tbl.' WHERE id = '.$this->db->escape($id).' LIMIT 1'
        );
        if (isset($row['val'])) {
            return $row['val'];
        }

        return 0;
    }

    public function getLock($t_out = 10, $t_out_init = '')
    {
        if (!$t_out_init) {
            $t_out_init = $t_out;
        }

        $l_name = $this->a['db_name'].'.'.$this->getTablePrefix().'.write_lock';
        $row = $this->db->fetchRow('SELECT IS_FREE_LOCK("'.$l_name.'") AS success');

        if (is_array($row)) {
            if (!$row['success']) {
                if ($t_out) {
                    sleep(1);

                    return $this->getLock($t_out - 1, $t_out_init);
                }
            } else {
                $row = $this->db->fetchRow('SELECT GET_LOCK("'.$l_name.'", '.$t_out_init.') AS success');
                if (isset($row['success'])) {
                    return $row['success'];
                }
            }
        }

        return 0;
    }

    public function releaseLock()
    {
        $sql = 'DO RELEASE_LOCK("'.$this->a['db_name'].'.'.$this->getTablePrefix().'.write_lock")';

        return $this->db->simpleQuery($sql);
    }

    /**
     * @deprecated
     */
    public function processTables($level = 2, $operation = 'optimize')
    {
        /*
         * level:
         *      1. triple + g2t
         *      2. triple + *2val
         *      3. all tables
         */
        $pre = $this->getTablePrefix();
        $tbls = $this->getTables();
        $sql = '';
        foreach ($tbls as $tbl) {
            if (($level < 3) && preg_match('/(backup|setting)$/', $tbl)) {
                continue;
            }
            if (($level < 2) && preg_match('/(val)$/', $tbl)) {
                continue;
            }
            $sql .= $sql ? ', ' : strtoupper($operation).' TABLE ';
            $sql .= $pre.$tbl;
        }
        $this->db->simpleQuery($sql);
        if (false == empty($this->db->getErrorMessage())) {
            $this->addError($this->db->getErrorMessage().' in '.$sql);
        }
    }

    /**
     * @deprecated
     */
    public function optimizeTables($level = 2)
    {
        if ($this->v('ignore_optimization')) {
            return 1;
        }

        return $this->processTables($level, 'optimize');
    }

    /**
     * @deprecated
     */
    public function checkTables($level = 2)
    {
        return $this->processTables($level, 'check');
    }

    /**
     * @deprecated
     */
    public function repairTables($level = 2)
    {
        return $this->processTables($level, 'repair');
    }

    public function changeNamespaceURI($old_uri, $new_uri)
    {
        ARC2::inc('StoreHelper');
        $c = new ARC2_StoreHelper($this->a, $this);

        return $c->changeNamespaceURI($old_uri, $new_uri);
    }

    /**
     * @param string $res           URI
     * @param string $unnamed_label How to label a resource without a name?
     *
     * @return string
     */
    public function getResourceLabel($res, $unnamed_label = 'An unnamed resource')
    {
        // init local label cache, if not set
        if (!isset($this->resource_labels)) {
            $this->resource_labels = [];
        }
        // if we already know the label for the given resource
        if (isset($this->resource_labels[$res])) {
            return $this->resource_labels[$res];
        }
        // if no URI was given, assume its a literal and return it
        if (!preg_match('/^[a-z0-9\_]+\:[^\s]+$/si', $res)) {
            return $res;
        }

        $ps = $this->getLabelProps();
        if ($this->getSetting('store_label_properties', '-') != md5(serialize($ps))) {
            $this->inferLabelProps($ps);
        }

        foreach ($ps as $labelProperty) {
            // send a query for each label property
            $result = $this->query('SELECT ?label WHERE { <'.$res.'> <'.$labelProperty.'> ?label }');
            if (isset($result['result']['rows'][0])) {
                $this->resource_labels[$res] = $result['result']['rows'][0]['label'];

                return $result['result']['rows'][0]['label'];
            }
        }

        $r = preg_replace("/^(.*[\/\#])([^\/\#]+)$/", '\\2', str_replace('#self', '', $res));
        $r = str_replace('_', ' ', $r);
        $r = preg_replace_callback('/([a-z])([A-Z])/', function ($matches) {
            return $matches[1].' '.strtolower($matches[2]);
        }, $r);

        return $r;
    }

    public function getLabelProps()
    {
        return array_merge(
            $this->v('rdf_label_properties', [], $this->a),
            [
                'http://www.w3.org/2000/01/rdf-schema#label',
                'http://xmlns.com/foaf/0.1/name',
                'http://purl.org/dc/elements/1.1/title',
                'http://purl.org/rss/1.0/title',
                'http://www.w3.org/2004/02/skos/core#prefLabel',
                'http://xmlns.com/foaf/0.1/nick',
            ]
        );
    }

    public function inferLabelProps($ps)
    {
        $this->query('DELETE FROM <label-properties>');
        $sub_q = '';
        foreach ($ps as $p) {
            $sub_q .= ' <'.$p.'> a <http://semsol.org/ns/arc#LabelProperty> . ';
        }
        $this->query('INSERT INTO <label-properties> { '.$sub_q.' }');
        $this->setSetting('store_label_properties', md5(serialize($ps)));
    }

    public function getResourcePredicates($res)
    {
        $r = [];
        $rows = $this->query('SELECT DISTINCT ?p WHERE { <'.$res.'> ?p ?o . }', 'rows');
        foreach ($rows as $row) {
            $r[$row['p']] = [];
        }

        return $r;
    }

    public function getDomains($p)
    {
        $r = [];
        foreach ($this->query('SELECT DISTINCT ?type WHERE {?s <'.$p.'> ?o ; a ?type . }', 'rows') as $row) {
            $r[] = $row['type'];
        }

        return $r;
    }

    public function getPredicateRange($p)
    {
        $row = $this->query('SELECT ?val WHERE {<'.$p.'> rdfs:range ?val . } LIMIT 1', 'row');

        return $row ? $row['val'] : '';
    }

    /**
     * @param string $q
     *
     * @todo make file path configurable
     * @todo add try/catch in case file creation/writing fails
     */
    public function logQuery($q)
    {
        $fp = fopen('arc_query_log.txt', 'a');
        fwrite($fp, date('Y-m-d\TH:i:s\Z', time()).' : '.$q.''."\n\n");
        fclose($fp);
    }
}
