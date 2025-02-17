<?php
/**
 * ARC2 RDF Store LOAD Query Handler.
 *
 * @author Benjamin Nowack
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 */
ARC2::inc('StoreQueryHandler');

class ARC2_StoreLoadQueryHandler extends ARC2_StoreQueryHandler
{
    public string $column_type;
    public string $fixed_target_graph;
    public int $has_lock;
    public int $keep_bnode_ids;
    public int $log_inserts;

    /**
     * @var array<mixed>
     */
    public array $inserts;

    /**
     * @var array<mixed>
     */
    public array $insert_times;

    /**
     * @var int
     */
    public $max_term_id;

    /**
     * @var int
     */
    public $max_triple_id;

    public int $split_threshold;

    /**
     * @var array<mixed>
     */
    public array $sql_buffers;

    public int $strip_mb_comp_str;
    public string $target_graph;

    /**
     * @var array<mixed>
     */
    public array $term_ids;

    /**
     * @var array<mixed>
     */
    public array $triple_ids;

    public int $t_count;
    public int $t_count_prev;

    /**
     * @var string|float
     */
    public $t_prev;

    /**
     * @var string|float
     */
    public $t_start;

    public int $write_buffer_size;

    public function __construct($a, &$caller)
    {
        /* caller has to be a store */
        parent::__construct($a, $caller);
    }

    public function setStore(ARC2_Store $store): void
    {
        $this->store = $store;
    }

    public function __init()
    {
        /* db_con, store_log_inserts */
        parent::__init();
        $this->store = $this->caller;
        $this->write_buffer_size = $this->v('store_write_buffer', 2500, $this->a);
        $this->split_threshold = $this->v('store_split_threshold', 0, $this->a);
        $this->strip_mb_comp_str = $this->v('store_strip_mb_comp_str', 0, $this->a);
    }

    public function runQuery($infos, $data = '', $keep_bnode_ids = 0)
    {
        $url = $infos['query']['url'];
        $graph = $infos['query']['target_graph'];
        $this->target_graph = $graph ? $this->calcURI($graph) : $this->calcURI($url);
        $this->fixed_target_graph = $graph ? $this->target_graph : '';
        $this->keep_bnode_ids = $keep_bnode_ids;
        /* reader */
        ARC2::inc('Reader');
        $reader = new ARC2_Reader($this->a, $this);
        $reader->activate($url, $data);
        /* format detection */
        $mappings = [
            'rdfxml' => 'RDFXML',
            'sparqlxml' => 'SPOG',
            'turtle' => 'Turtle',
            'ntriples' => 'Turtle',
            'rss' => 'RSS',
            'atom' => 'Atom',
            'n3' => 'Turtle',
            'html' => 'SemHTML',
            'sgajson' => 'SGAJSON',
            'cbjson' => 'CBJSON',
        ];
        $format = $reader->getFormat();
        if (!$format || !isset($mappings[$format])) {
            return $this->addError('No loader available for "'.$url.'": '.$format);
        }
        /* format loader */
        $suffix = 'Store'.$mappings[$format].'Loader';
        ARC2::inc($suffix);
        $cls = 'ARC2_'.$suffix;
        $loader = new $cls($this->a, $this);
        $loader->setReader($reader);
        /* lock */
        if (!$this->store->getLock()) {
            $l_name = $this->a['db_name'].'.'.$this->store->getTablePrefix().'.write_lock';

            return $this->addError('Could not get lock in "runQuery" ('.$l_name.')');
        }
        $this->has_lock = 1;
        /* logging */
        $this->t_count = 0;
        $this->t_start = ARC2::mtime();
        $this->log_inserts = $this->v('store_log_inserts', 0, $this->a);
        if ($this->log_inserts) {
            if (file_exists('arc_insert_log.txt')) {
                unlink('arc_insert_log.txt');
            }
            $this->inserts = [];
            $this->insert_times = [];
            $this->t_prev = $this->t_start;
            $this->t_count_prev = 0;
        }
        /* load and parse */
        $this->max_term_id = $this->getMaxTermID();
        $this->max_triple_id = $this->getMaxTripleID();
        $this->column_type = $this->store->getColumnType();

        $this->term_ids = [];
        $this->triple_ids = [];
        $this->sql_buffers = [];
        $r = $loader->parse($url, $data);

        /* done */
        $this->checkSQLBuffers(1);
        if ($this->log_inserts) {
            $this->logInserts();
        }
        $this->store->releaseLock();

        if (1 == rand(1, 100)) {
            $this->store->optimizeTables();
        }

        $t2 = ARC2::mtime();
        $dur = round($t2 - $this->t_start, 4);
        $r = [
            't_count' => $this->t_count,
            'load_time' => $dur,
        ];

        if ($this->log_inserts) {
            $r['inserts'] = $this->inserts;
            $r['insert_times'] = $this->insert_times;
        }

        return $r;
    }

    public function addT($s, $p, $o, $s_type, $o_type, $o_dt = '', $o_lang = '')
    {
        if (!$this->has_lock) {
            return 0;
        }
        $type_ids = ['uri' => '0', 'bnode' => '1', 'literal' => '2'];
        $g = $this->getStoredTermID($this->target_graph, '0', 'id');
        $s = (('bnode' == $s_type) && !$this->keep_bnode_ids) ? '_:b'.abs(crc32($g.$s)).'_'.(strlen($s) > 12 ? substr(substr($s, 2), -10) : substr($s, 2)) : $s;
        $o = (('bnode' == $o_type) && !$this->keep_bnode_ids) ? '_:b'.abs(crc32($g.$o)).'_'.(strlen($o) > 12 ? substr(substr($o, 2), -10) : substr($o, 2)) : $o;
        /* triple */
        $t = [
            's' => $this->getStoredTermID($s, $type_ids[$s_type], 's'),
            'p' => $this->getStoredTermID($p, '0', 'id'),
            'o' => $this->getStoredTermID($o, $type_ids[$o_type], 'o'),
            'o_lang_dt' => $this->getStoredTermID($o_dt.$o_lang, $o_dt ? '0' : '2', 'id'),
            'o_comp' => $this->getOComp($o),
            's_type' => $type_ids[$s_type],
            'o_type' => $type_ids[$o_type],
        ];
        $t['t'] = $this->getTripleID($t);
        if (is_array($t['t'])) {/* t exists already */
            $t['t'] = $t['t'][0];
        } else {
            $this->bufferTripleSQL($t);
        }
        /* g2t */
        $g2t = ['g' => $g, 't' => $t['t']];
        $this->bufferGraphSQL($g2t);
        ++$this->t_count;
        /* check buffers */
        if (0 == ($this->t_count % $this->write_buffer_size)) {
            $force_write = 1;
            $reset_buffers = (0 == ($this->t_count % ($this->write_buffer_size * 2)));
            $refresh_lock = (0 == ($this->t_count % 25000));
            $split_tables = (0 == ($this->t_count % ($this->write_buffer_size * 10)));
            if ($this->log_inserts) {
                $this->logInserts();
            }
            $this->checkSQLBuffers($force_write, $reset_buffers, $refresh_lock, $split_tables);
        }
    }

    public function getMaxTermID()
    {
        $sql = '';
        foreach (['id2val', 's2val', 'o2val'] as $tbl) {
            $sql .= $sql ? ' UNION ' : '';
            $sql .= '(SELECT MAX(id) as id FROM '.$this->store->getTablePrefix().$tbl.')';
        }
        $r = 0;

        $rows = $this->store->a['db_object']->fetchList($sql);

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $r = ($r < $row['id']) ? $row['id'] : $r;
            }
        }

        return $r + 1;
    }

    /**
     * @todo change DB schema and avoid using this function because it does not protect against race conditions
     *
     * @return int
     */
    public function getMaxTripleID()
    {
        $sql = 'SELECT MAX(t) AS `id` FROM '.$this->store->getTablePrefix().'triple';

        $row = $this->store->a['db_object']->fetchRow($sql);
        if (isset($row['id'])) {
            return $row['id'] + 1;
        }

        return 1;
    }

    public function getStoredTermID($val, $type_id, $tbl)
    {
        /* buffered */
        if (isset($this->term_ids[$val])) {
            if (!isset($this->term_ids[$val][$tbl])) {
                foreach (['id', 's', 'o'] as $other_tbl) {
                    if (isset($this->term_ids[$val][$other_tbl])) {
                        $this->term_ids[$val][$tbl] = $this->term_ids[$val][$other_tbl];
                        $this->bufferIDSQL($tbl, $this->term_ids[$val][$tbl], $val, $type_id);
                        break;
                    }
                }
            }

            return $this->term_ids[$val][$tbl];
        }
        /* db */
        $tbl_prefix = $this->store->getTablePrefix();
        $sub_tbls = ('id' == $tbl)
            ? ['id2val', 's2val', 'o2val']
            : ('s' == $tbl
                ? ['s2val', 'id2val', 'o2val']
                : ['o2val', 'id2val', 's2val']
            );

        foreach ($sub_tbls as $sub_tbl) {
            $id = 0;
            /* via hash */
            if (preg_match('/^(s2val|o2val)$/', $sub_tbl) && $this->hasHashColumn($sub_tbl)) {
                $sql = 'SELECT id, val
                       FROM '.$tbl_prefix.$sub_tbl."
                      WHERE val_hash = BINARY '".$this->getValueHash($val)."'";

                $rows = $this->store->a['db_object']->fetchList($sql);
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        if ($row['val'] == $val) {
                            $id = $row['id'];
                            break;
                        }
                    }
                }
            } else {
                $binaryValue = $this->store->a['db_object']->escape($val);
                // Updated by Craig Dietrich 19 February 2020:
                // Always let through, even if empty since the id2val is storing empty values, too
                $sql = 'SELECT id AS `id` FROM '.$tbl_prefix.$sub_tbl." WHERE val = BINARY '".$binaryValue."'";
                $row = $this->store->a['db_object']->fetchRow($sql);
                if (is_array($row) && isset($row['id'])) {
                    $id = $row['id'];
                }
            }
            if ($id) {
                $this->term_ids[$val] = [$tbl => $id];
                if ($sub_tbl != $tbl.'2val') {
                    $this->bufferIDSQL($tbl, $id, $val, $type_id);
                }
                break;
            }
        }
        /* new */
        if (!isset($this->term_ids[$val])) {
            $this->term_ids[$val] = [$tbl => $this->max_term_id];
            $this->bufferIDSQL($tbl, $this->max_term_id, $val, $type_id);
            ++$this->max_term_id;
            /*
             * upgrade tables ?
             *
             * TODO: on next major release, remove that and find a way to use bigger version automatically.
             */
            if (('mediumint' == $this->column_type) && ($this->max_term_id >= 16750000)) {
                $this->store->extendColumns();
                $this->column_type = 'int';
            }
        }

        return $this->term_ids[$val][$tbl];
    }

    public function getTripleID($t)
    {
        $val = serialize($t);
        /* buffered */
        if (isset($this->triple_ids[$val])) {
            return [$this->triple_ids[$val]]; /* hack for "don't insert this triple" */
        }
        /* db */
        $sql = 'SELECT t
                  FROM '.$this->store->getTablePrefix().'triple
                 WHERE s = '.$t['s'].'
                    AND p = '.$t['p'].'
                    AND o = '.$t['o'].'
                    AND o_lang_dt = '.$t['o_lang_dt'].'
                    AND s_type = '.$t['s_type'].'
                    AND o_type = '.$t['o_type'].'
                 LIMIT 1';
        $row = $this->store->a['db_object']->fetchRow($sql);
        if (isset($row['t'])) {
            $this->triple_ids[$val] = $row['t']; /* hack for "don't insert this triple" */

            return [$row['t']]; /* hack for "don't insert this triple" */
        } else {
            /* new */
            $this->triple_ids[$val] = $this->max_triple_id;
            ++$this->max_triple_id;
            /* split tables ? */
            if (0 && $this->split_threshold && !($this->max_triple_id % $this->split_threshold)) {
                $this->store->splitTables();
                $this->dropMergeTable();
                $this->createMergeTable();
            }
            /* upgrade tables ? // Thanks to patch by Mark Fichtner (https://github.com/Knurg) */
            if (('mediumint' == $this->column_type) && ($this->max_triple_id >= 16750000)) {
                $this->store->extendColumns();
                $this->column_type = 'int';
            }

            return $this->triple_ids[$val];
        }
    }

    public function getOComp($val)
    {
        /* try date (e.g. 21 August 2007) */
        if (
            preg_match('/^[0-9]{1,2}\s+[a-z]+\s+[0-9]{4}/i', $val)
            && ($uts = strtotime($val))
            && (-1 !== $uts)
        ) {
            return date("Y-m-d\TH:i:s", $uts);
        }

        /* xsd date (e.g. 2009-05-28T18:03:38+09:00 2009-05-28T18:03:38GMT) */
        if (true === (bool) strtotime($val)) {
            return date('Y-m-d\TH:i:s\Z', strtotime($val));
        }

        if (is_numeric($val)) {
            $val = sprintf('%f', $val);
            if (preg_match("/([\-\+])([0-9]*)\.([0-9]*)/", $val, $m)) {
                return $m[1].sprintf('%018s', $m[2]).'.'.sprintf('%-015s', $m[3]);
            }
            if (preg_match("/([0-9]*)\.([0-9]*)/", $val, $m)) {
                return '+'.sprintf('%018s', $m[1]).'.'.sprintf('%-015s', $m[2]);
            }

            return $val;
        }

        /* any other string: remove tags, linebreaks etc., but keep MB-chars */
        // [\PL\s]+ ( = non-Letters) kills digits
        $re = $this->has_pcre_unicode ? '/[\PL\s]+/isu' : '/[\s\'\"\´\`]+/is';
        $re = '/[\s\'\"\´\`]+/is';
        $val = trim(preg_replace($re, '-', strip_tags($val)));
        if (strlen($val) > 35) {
            $fnc = function_exists('mb_substr') ? 'mb_substr' : 'substr';
            $val = $fnc($val, 0, 17).'-'.$fnc($val, -17);
        }
        if ($this->strip_mb_comp_str) {
            $val = urldecode(preg_replace('/\%[0-9A-F]{2}/', '', urlencode($val)));
        }

        return $this->toUTF8($val);
    }

    public function bufferTripleSQL($t)
    {
        $tbl = 'triple';
        $sql = ', ';

        $sqlHead = 'INSERT IGNORE INTO ';

        if (!isset($this->sql_buffers[$tbl])) {
            $this->sql_buffers[$tbl] = $sqlHead;
            $this->sql_buffers[$tbl] .= $this->store->getTablePrefix().$tbl;
            $this->sql_buffers[$tbl] .= ' (t, s, p, o, o_lang_dt, o_comp, s_type, o_type) VALUES';
            $sql = ' ';
        }

        $oCompEscaped = $this->store->a['db_object']->escape($t['o_comp']);

        $this->sql_buffers[$tbl] .= $sql.'('.$t['t'].', '.$t['s'].', '.$t['p'].', ';
        $this->sql_buffers[$tbl] .= $t['o'].', '.$t['o_lang_dt'].", '";
        $this->sql_buffers[$tbl] .= $oCompEscaped."', ".$t['s_type'].', '.$t['o_type'].')';
    }

    public function bufferGraphSQL($g2t)
    {
        $tbl = 'g2t';
        $sql = ', ';

        $sqlHead = 'INSERT IGNORE INTO ';

        if (!isset($this->sql_buffers[$tbl])) {
            $this->sql_buffers[$tbl] = $sqlHead.$this->store->getTablePrefix().$tbl.' (g, t) VALUES';
            $sql = ' ';
        }
        $this->sql_buffers[$tbl] .= $sql.'('.$g2t['g'].', '.$g2t['t'].')';
    }

    public function bufferIDSQL($tbl, $id, $val, $val_type)
    {
        $tbl .= '2val';
        if ('id2val' == $tbl) {
            $cols = 'id, val, val_type';
            $vals = '('.$id.", '".$this->store->a['db_object']->escape($val)."', ".$val_type.')';
        } elseif (preg_match('/^(s2val|o2val)$/', $tbl) && $this->hasHashColumn($tbl)) {
            $cols = 'id, val_hash, val';
            $vals = '('.$id.", '".$this->getValueHash($val)."', '".$this->store->a['db_object']->escape($val)."')";
        } else {
            $cols = 'id, val';
            $vals = '('.$id.", '".$this->store->a['db_object']->escape($val)."')";
        }
        if (!isset($this->sql_buffers[$tbl])) {
            $this->sql_buffers[$tbl] = '';

            $sqlHead = 'INSERT IGNORE INTO ';
            $sql = $sqlHead.$this->store->getTablePrefix().$tbl.'('.$cols.') VALUES ';
        } else {
            $sql = ', ';
        }
        $sql .= $vals;
        $this->sql_buffers[$tbl] .= $sql;
    }

    public function checkSQLBuffers(
        $force_write = 0,
        $reset_id_buffers = 0,
        $refresh_lock = 0
    ) {
        if (!$this->keep_time_limit) {
            set_time_limit($this->v('time_limit', 60, $this->a));
        }
        foreach (['triple', 'g2t', 'id2val', 's2val', 'o2val'] as $tbl) {
            $buffer_size = isset($this->sql_buffers[$tbl]) ? 1 : 0;
            if ($buffer_size && $force_write) {
                $t1 = ARC2::mtime();
                $this->store->a['db_object']->simpleQuery($this->sql_buffers[$tbl]);
                /* table error */
                $error = $this->store->a['db_object']->getErrorMessage();
                if (!empty($error)) {
                    $this->autoRepairTable($error, $this->sql_buffers[$tbl]);
                }
                unset($this->sql_buffers[$tbl]);
                if ($this->log_inserts) {
                    $t2 = ARC2::mtime();
                    $this->inserts[$tbl] = $this->v(
                        $tbl,
                        0,
                        $this->inserts
                    ) + max(0, $this->store->a['db_object']->getAffectedRows());

                    $dur = round($t2 - $t1, 4);
                    $this->insert_times[$tbl] = isset($this->insert_times[$tbl])
                        ? $this->insert_times[$tbl]
                        : ['min' => $dur, 'max' => $dur, 'sum' => $dur];
                    $this->insert_times[$tbl] = [
                        'min' => min($dur, $this->insert_times[$tbl]['min']),
                        'max' => max($dur, $this->insert_times[$tbl]['max']),
                        'sum' => $dur + $this->insert_times[$tbl]['sum'],
                    ];
                }
                /* reset term id buffers */
                if ($reset_id_buffers) {
                    $this->term_ids = [];
                    $this->triple_ids = [];
                }
                /* refresh lock */
                if ($refresh_lock) {
                    $this->store->releaseLock();
                    $this->has_lock = 0;
                    sleep(1);
                    if (!$this->store->getLock(5)) {
                        return $this->addError('Could not re-obtain lock in "checkSQLBuffers"');
                    }
                    $this->has_lock = 1;
                }
            }
        }

        return 1;
    }

    public function autoRepairTable($er, $sql = '')
    {
        $this->addError('MySQL error: '.$er.' ('.$sql.')');
        if (preg_match('/Table \'[^\']+\/([a-z0-9\_\-]+)\' .*(crashed|repair)/i', $er, $m)) {
            $row = $this->store->a['db_object']->fetchRow('REPAIR TABLE '.rawurlencode($m[1]));
            $msg = is_array($row) ? $row : [];

            if ('error' == $this->v('Msg_type', 'error', $msg)) {
                /* auto-reset */
                if ($this->v('store_reset_on_table_crash', 0, $this->a)) {
                    $this->store->drop();
                    $this->store->setUp();
                } else {
                    $er = $this->v('Msg_text', 'unknown error', $msg);
                    $this->addError('Auto-repair failed on '.rawurlencode($m[1]).': '.$er);
                }
            }
        }
    }

    /* speed log */

    public function logInserts()
    {
        $t_start = $this->t_start;
        $t_prev = $this->t_prev;
        $t_now = ARC2::mtime();
        $tc_prev = $this->t_count_prev;
        $tc_now = $this->t_count;
        $tc_diff = $tc_now - $tc_prev;

        $dur_full = $t_now - $t_start;
        $dur_diff = $t_now - $t_prev;

        $speed_full = round($tc_now / $dur_full);
        $speed_now = round($tc_diff / $dur_diff);

        $r = $tc_diff.' in '.round($dur_diff, 5).' = '.$speed_now.' t/s  ('.$tc_now.' in '.round($dur_full, 5).' = '.$speed_full.' t/s )';
        $fp = fopen(__DIR__.'/../arc_insert_log.txt', 'a');
        fwrite($fp, $r."\r\n");
        fclose($fp);

        $this->t_prev = $t_now;
        $this->t_count_prev = $tc_now;
    }
}
