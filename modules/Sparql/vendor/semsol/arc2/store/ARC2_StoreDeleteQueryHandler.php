<?php
/**
 * ARC2 RDF Store DELETE Query Handler.
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 */
ARC2::inc('StoreQueryHandler');

class ARC2_StoreDeleteQueryHandler extends ARC2_StoreQueryHandler
{
    /**
     * @var array<mixed>
     */
    public array $infos;

    public $refs_deleted;

    public function __construct($a, &$caller)
    {/* caller has to be a store */
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->store = $this->caller;
        $this->handler_type = 'delete';
    }

    public function runQuery($infos)
    {
        $this->infos = $infos;
        $t1 = ARC2::mtime();
        /* delete */
        $this->refs_deleted = false;
        /* graph(s) only */
        if (!$this->v('construct_triples', [], $this->infos['query'])) {
            $tc = $this->deleteTargetGraphs();
        }
        /* graph(s) + explicit triples */
        elseif (!$this->v('pattern', [], $this->infos['query'])) {
            $tc = $this->deleteTriples();
        }
        /* graph(s) + constructed triples */
        else {
            $tc = $this->deleteConstructedGraph();
        }
        $t2 = ARC2::mtime();
        /* clean up */
        if ($tc && ($this->refs_deleted || (1 == rand(1, 100)))) {
            $this->cleanTableReferences();
        }
        // TODO What does this rand() call here? remove it and think about a cleaner way
        //      when to trigger optimizeTables
        if ($tc && (1 == rand(1, 100))) {
            $this->store->optimizeTables();
        }
        // TODO What does this rand() call here? remove it and think about a cleaner way
        //      when to trigger cleanValueTables
        if ($tc && (1 == rand(1, 500))) {
            $this->cleanValueTables();
        }
        $t3 = ARC2::mtime();
        $index_dur = round($t3 - $t2, 4);
        $dur = round($t3 - $t1, 4);

        return [
            't_count' => $tc,
            'delete_time' => $dur,
            'index_update_time' => $index_dur,
        ];
    }

    public function deleteTargetGraphs()
    {
        $tbl_prefix = $this->store->getTablePrefix();
        $r = 0;
        foreach ($this->infos['query']['target_graphs'] as $g) {
            if ($g_id = $this->getTermID($g, 'g')) {
                $r += $this->store->a['db_object']->exec('DELETE FROM '.$tbl_prefix.'g2t WHERE g = '.$g_id);
            }
        }
        $this->refs_deleted = $r ? 1 : 0;

        return $r;
    }

    public function deleteTriples()
    {
        $r = 0;
        $dbv = $this->store->getDBVersion();
        $tbl_prefix = $this->store->getTablePrefix();
        /* graph restriction */
        $tgs = $this->infos['query']['target_graphs'];
        $gq = '';
        foreach ($tgs as $g) {
            if ($g_id = $this->getTermID($g, 'g')) {
                $gq .= $gq ? ', '.$g_id : $g_id;
            }
        }
        $gq = $gq ? ' AND G.g IN ('.$gq.')' : '';
        /* triples */
        foreach ($this->infos['query']['construct_triples'] as $t) {
            $q = '';
            $skip = 0;
            foreach (['s', 'p', 'o'] as $term) {
                if (isset($t[$term.'_type']) && preg_match('/(var)/', $t[$term.'_type'])) {
                    // $skip = 1;
                } else {
                    $term_id = $this->getTermID($t[$term], $term);
                    $q .= ($q ? ' AND ' : '').'T.'.$term.'='.$term_id;
                    /* explicit lang/dt restricts the matching */
                    if ('o' == $term) {
                        $o_lang = $this->v1('o_lang', '', $t);
                        $o_lang_dt = $this->v1('o_datatype', $o_lang, $t);
                        if ($o_lang_dt) {
                            $q .= ($q ? ' AND ' : '').'T.o_lang_dt='.$this->getTermID($o_lang_dt, 'lang_dt');
                        }
                    }
                }
            }
            if ($skip) {
                continue;
            }
            if ($gq) {
                $sql = ($dbv < '04-01') ? 'DELETE '.$tbl_prefix.'g2t' : 'DELETE G';
                $sql .= '
                    FROM '.$tbl_prefix.'g2t G
                    JOIN '.$this->getTripleTable().' T ON (T.t = G.t'.$gq.')
                    WHERE '.$q.'
                    ';
                $this->refs_deleted = 1;
            } else {/* triples only */
                $sql = ($dbv < '04-01') ? 'DELETE '.$this->getTripleTable() : 'DELETE T';
                $sql .= ' FROM '.$this->getTripleTable().' T WHERE '.$q;
            }
            $r += $this->store->a['db_object']->exec($sql);
            if (!empty($this->store->a['db_object']->getErrorMessage())) {
                $this->addError($this->store->a['db_object']->getErrorMessage().' in '.$sql);
            }
        }

        return $r;
    }

    public function deleteConstructedGraph()
    {
        ARC2::inc('StoreConstructQueryHandler');
        $h = new ARC2_StoreConstructQueryHandler($this->a, $this->store);
        $sub_r = $h->runQuery($this->infos);
        $triples = ARC2::getTriplesFromIndex($sub_r);
        $tgs = $this->infos['query']['target_graphs'];
        $this->infos = ['query' => ['construct_triples' => $triples, 'target_graphs' => $tgs]];

        return $this->deleteTriples();
    }

    public function cleanTableReferences()
    {
        /* lock */
        if (!$this->store->getLock()) {
            return $this->addError('Could not get lock in "cleanTableReferences"');
        }
        $tbl_prefix = $this->store->getTablePrefix();
        $dbv = $this->store->getDBVersion();
        /* check for unconnected triples */
        $sql = '
      SELECT T.t FROM '.$tbl_prefix.'triple T LEFT JOIN '.$tbl_prefix.'g2t G ON ( G.t = T.t )
      WHERE G.t IS NULL LIMIT 1
    ';
        $numRows = $this->store->a['db_object']->getNumberOfRows($sql);
        if (0 < $numRows) {
            /* delete unconnected triples */
            $sql = 'DELETE T
                FROM '.$tbl_prefix.'triple T
                LEFT JOIN '.$tbl_prefix.'g2t G ON (G.t = T.t)
                WHERE G.t IS NULL
            ';
            $this->store->a['db_object']->simpleQuery($sql);
        }
        /* check for unconnected graph refs */
        if (1 == rand(1, 10)) {
            $sql = '
                SELECT G.g FROM '.$tbl_prefix.'g2t G LEFT JOIN '.$tbl_prefix.'triple T ON ( T.t = G.t )
                WHERE T.t IS NULL LIMIT 1
            ';
            if (0 < $this->store->a['db_object']->getNumberOfRows($sql)) {
                /* delete unconnected graph refs */
                $sql = ($dbv < '04-01') ? 'DELETE '.$tbl_prefix.'g2t' : 'DELETE G';
                $sql .= '
                    FROM '.$tbl_prefix.'g2t G
                    LEFT JOIN '.$tbl_prefix.'triple T ON (T.t = G.t)
                    WHERE T.t IS NULL
                ';
                $this->store->a['db_object']->simpleQuery($sql);
            }
        }
        /* release lock */
        $this->store->releaseLock();
    }

    public function cleanValueTables()
    {
        /* lock */
        if (!$this->store->getLock()) {
            return $this->addError('Could not get lock in "cleanValueTables"');
        }
        $tbl_prefix = $this->store->getTablePrefix();
        $dbv = $this->store->getDBVersion();

        /* o2val */
        $sql = ($dbv < '04-01') ? 'DELETE '.$tbl_prefix.'o2val' : 'DELETE V';
        $sql .= '
      FROM '.$tbl_prefix.'o2val V
      LEFT JOIN '.$tbl_prefix.'triple T ON (T.o = V.id)
      WHERE T.t IS NULL
    ';
        $this->store->a['db_object']->simpleQuery($sql);

        /* s2val */
        $sql = ($dbv < '04-01') ? 'DELETE '.$tbl_prefix.'s2val' : 'DELETE V';
        $sql .= '
      FROM '.$tbl_prefix.'s2val V
      LEFT JOIN '.$tbl_prefix.'triple T ON (T.s = V.id)
      WHERE T.t IS NULL
    ';
        $this->store->a['db_object']->simpleQuery($sql);

        /* id2val */
        $sql = ($dbv < '04-01') ? 'DELETE '.$tbl_prefix.'id2val' : 'DELETE V';
        $sql .= '
      FROM '.$tbl_prefix.'id2val V
      LEFT JOIN '.$tbl_prefix.'g2t G ON (G.g = V.id)
      LEFT JOIN '.$tbl_prefix.'triple T1 ON (T1.p = V.id)
      LEFT JOIN '.$tbl_prefix.'triple T2 ON (T2.o_lang_dt = V.id)
      WHERE G.g IS NULL AND T1.t IS NULL AND T2.t IS NULL
    ';
        // TODO was commented out before. could this be a problem?
        $this->store->a['db_object']->simpleQuery($sql);

        /* release lock */
        $this->store->releaseLock();
    }
}
