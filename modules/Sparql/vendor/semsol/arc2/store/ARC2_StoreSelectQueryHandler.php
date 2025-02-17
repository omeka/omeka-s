<?php
/**
 * ARC2 RDF Store SELECT Query Handler.
 *
 * @author    Benjamin Nowack
 * @license   W3C Software License and GPL
 *
 * @homepage  <https://github.com/semsol/arc2>
 *
 * @version   2010-11-16
 */
ARC2::inc('StoreQueryHandler');

class ARC2_StoreSelectQueryHandler extends ARC2_StoreQueryHandler
{
    public int $cache_results;

    /**
     * @var array<mixed>
     */
    public array $dependency_log;

    public string $engine_type;

    /**
     * @var array<miyed>
     */
    public array $index;

    /**
     * @var array<miyed>
     */
    public array $indexes;

    /**
     * @var array<miyed>
     */
    public array $initial_index;

    public int $is_union_query;

    /**
     * @var array<miyed>
     */
    public array $infos;

    public $opt_sql;

    public int $opt_sql_pd_count;

    public int $pattern_order_offset;

    public function __construct($a, &$caller)
    {/* caller has to be a store */
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->store = $this->caller;
        $this->handler_type = 'select';
        $this->engine_type = $this->v('store_engine_type', 'InnoDB', $this->a);
        $this->cache_results = $this->v('store_cache_results', 0, $this->a);
    }

    public function runQuery($infos)
    {
        $rf = $this->v('result_format', '', $infos);
        $this->infos = $infos;
        $this->infos['null_vars'] = [];
        $this->indexes = [];
        $this->pattern_order_offset = 0;
        $q_sql = $this->getSQL();

        /* debug result formats */
        if ('sql' == $rf) {
            return $q_sql;
        }
        if ('structure' == $rf) {
            return $this->infos;
        }
        if ('index' == $rf) {
            return $this->indexes;
        }
        /* create intermediate results (ID-based) */
        $tmp_tbl = $this->createTempTable($q_sql);
        /* join values */
        $r = $this->getFinalQueryResult($q_sql, $tmp_tbl);
        /* remove intermediate results */
        if (!$this->cache_results) {
            $this->getDBObjectFromARC2Class()->simpleQuery('DROP TABLE IF EXISTS '.$tmp_tbl);
        }

        return $r;
    }

    public function getSQL()
    {
        $r = '';
        $nl = "\n";
        $this->buildInitialIndexes();
        foreach ($this->indexes as $i => $index) {
            $this->index = array_merge($this->getEmptyIndex(), $index);
            $this->analyzeIndex($this->getPattern('0'));
            $sub_r = $this->getQuerySQL();
            $r .= $r ? $nl.'UNION'.$this->getDistinctSQL().$nl : '';
            $r .= $this->is_union_query ? '('.$sub_r.')' : $sub_r;

            $this->indexes[$i] = $this->index;
        }
        $r .= $this->is_union_query ? $this->getLIMITSQL() : '';
        if ($this->v('order_infos', 0, $this->infos['query'])) {
            $r = preg_replace('/SELECT(\s+DISTINCT)?\s*/', 'SELECT\\1 NULL AS `TMPPOS`, ', $r);
        }
        $pd_count = $this->problematicDependencies();
        if ($pd_count) {
            /* re-arranging the patterns sometimes reduces the LEFT JOIN dependencies */
            $set_sql = 0;
            if (!$this->pattern_order_offset) {
                $set_sql = 1;
            }
            if (!$set_sql && ($pd_count < $this->opt_sql_pd_count)) {
                $set_sql = 1;
            }
            if (!$set_sql && ($pd_count == $this->opt_sql_pd_count) && (strlen($r) < strlen($this->opt_sql))) {
                $set_sql = 1;
            }
            if ($set_sql) {
                $this->opt_sql = $r;
                $this->opt_sql_pd_count = $pd_count;
            }
            ++$this->pattern_order_offset;
            if ($this->pattern_order_offset > 5) {
                return $this->opt_sql;
            }

            return $this->getSQL();
        }

        return $r;
    }

    public function buildInitialIndexes()
    {
        $this->dependency_log = [];
        $this->index = $this->getEmptyIndex();
        // if no pattern is in the query, the index "pattern" is undefined, which leads to an error.
        // TODO throw an exception/raise an error and avoid "Undefined index: pattern" notification
        $this->buildIndex($this->infos['query']['pattern'], 0);
        $tmp = $this->index;
        $this->analyzeIndex($this->getPattern('0'));
        $this->initial_index = $this->index;
        $this->index = $tmp;
        $this->is_union_query = $this->index['union_branches'] ? 1 : 0;
        $this->indexes = $this->is_union_query ? $this->getUnionIndexes($this->index) : [$this->index];
    }

    public function createTempTable($q_sql)
    {
        if ($this->cache_results) {
            $tbl = $this->store->getTablePrefix().'Q'.md5($q_sql);
        } else {
            $tbl = $this->store->getTablePrefix().'Q'.md5($q_sql.time().uniqid(rand()));
        }
        if (strlen($tbl) > 64) {
            $tbl = 'Q'.md5($tbl);
        }

        $tmp_sql = 'CREATE TEMPORARY TABLE '.$tbl.' ( ';
        $tmp_sql .= $this->getTempTableDefForMySQL($q_sql);
        /* HEAP doesn't support AUTO_INCREMENT, and MySQL breaks on MEMORY sometimes */
        $tmp_sql .= ') ENGINE='.$this->engine_type;

        $tmpSql2 = str_replace('CREATE TEMPORARY', 'CREATE', $tmp_sql);

        if (
            !$this->store->a['db_object']->simpleQuery($tmp_sql)
            && !$this->store->a['db_object']->simpleQuery($tmpSql2)
        ) {
            return $this->addError($this->store->a['db_object']->getErrorMessage());
        }
        if (false == $this->store->a['db_object']->exec('INSERT INTO '.$tbl.' '."\n".$q_sql)) {
            $this->addError($this->store->a['db_object']->getErrorMessage());
        }

        return $tbl;
    }

    public function getEmptyIndex()
    {
        return [
            'from' => [],
            'join' => [],
            'left_join' => [],
            'vars' => [], 'graph_vars' => [], 'graph_uris' => [],
            'bnodes' => [],
            'triple_patterns' => [],
            'sub_joins' => [],
            'constraints' => [],
            'union_branches' => [],
            'patterns' => [],
            'havings' => [],
        ];
    }

    public function getTempTableDefForMySQL($q_sql)
    {
        $col_part = preg_replace('/^SELECT\s*(DISTINCT)?(.*)FROM.*$/s', '\\2', $q_sql);
        $parts = explode(',', $col_part);
        $has_order_infos = $this->v('order_infos', 0, $this->infos['query']);
        $r = '';
        $added = [];
        foreach ($parts as $part) {
            if (preg_match('/\.?(.+)\s+AS\s+`(.+)`/U', trim($part), $m) && !isset($added[$m[2]])) {
                $alias = $m[2];
                if ('TMPPOS' == $alias) {
                    continue;
                }
                $r .= $r ? ',' : '';
                $r .= "\n `".$alias.'` int UNSIGNED';
                $added[$alias] = 1;
            }
        }
        if ($has_order_infos) {
            $r = "\n".'`TMPPOS` mediumint NOT NULL AUTO_INCREMENT PRIMARY KEY, '.$r;
        }

        return $r ? $r."\n" : '';
    }

    public function getTempTableDefForSQLite($q_sql)
    {
        $col_part = preg_replace('/^SELECT\s*(DISTINCT)?(.*)FROM.*$/s', '\\2', $q_sql);
        $parts = explode(',', $col_part);
        $has_order_infos = $this->v('order_infos', 0, $this->infos['query']);
        $r = '';
        $added = [];
        foreach ($parts as $part) {
            if (preg_match('/\.?(.+)\s+AS\s+`(.+)`/U', trim($part), $m) && !isset($added[$m[2]])) {
                $alias = $m[2];
                if ('TMPPOS' == $alias) {
                    continue;
                }
                $r .= $r ? ',' : '';
                $r .= "\n `".$alias.'` INTEGER UNSIGNED';
                $added[$alias] = 1;
            }
        }
        if ($has_order_infos) {
            $r = "\n".'`TMPPOS` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, '.$r;
        }

        return $r ? $r."\n" : '';
    }

    public function getFinalQueryResult($q_sql, $tmp_tbl)
    {
        /* var names */
        $vars = [];
        $aggregate_vars = [];
        foreach ($this->infos['query']['result_vars'] as $entry) {
            if ($entry['aggregate']) {
                $vars[] = $entry['alias'];
                $aggregate_vars[] = $entry['alias'];
            } else {
                $vars[] = $entry['var'];
            }
        }
        /* result */
        $r = ['variables' => $vars];
        $v_sql = $this->getValueSQL($tmp_tbl, $q_sql);

        $t1 = ARC2::mtime();

        try {
            $entries = []; // in case an exception gets thrown

            $entries = $this->store->a['db_object']->fetchList($v_sql);
        } catch (\Exception $e) {
            $this->addError($e->getMessage());
        }

        $rows = [];
        $types = [0 => 'uri', 1 => 'bnode', 2 => 'literal'];
        if (0 < count($entries)) {
            foreach ($entries as $pre_row) {
                $row = [];
                foreach ($vars as $var) {
                    if (isset($pre_row[$var])) {
                        $row[$var] = $pre_row[$var];
                        $row[$var.' type'] = isset($pre_row[$var.' type'])
                            ? $types[$pre_row[$var.' type']]
                            : (
                                in_array($var, $aggregate_vars)
                                    ? 'literal'
                                    : 'uri'
                            );
                        if (isset($pre_row[$var.' lang_dt']) && ($lang_dt = $pre_row[$var.' lang_dt'])) {
                            if (preg_match('/^([a-z]+(\-[a-z0-9]+)*)$/i', $lang_dt)) {
                                $row[$var.' lang'] = $lang_dt;
                            } else {
                                $row[$var.' datatype'] = $lang_dt;
                            }
                        }
                    }
                }
                if ($row || !$vars) {
                    $rows[] = $row;
                }
            }
        }
        $r['rows'] = $rows;

        return $r;
    }

    public function buildIndex($pattern, $id)
    {
        $pattern['id'] = $id;
        $type = $this->v('type', '', $pattern);
        if (('filter' == $type) && $this->v('constraint', 0, $pattern)) {
            $sub_pattern = $pattern['constraint'];
            $sub_pattern['parent_id'] = $id;
            $sub_id = $id.'_0';
            $this->buildIndex($sub_pattern, $sub_id);
            $pattern['constraint'] = $sub_id;
        } else {
            $sub_patterns = $this->v('patterns', [], $pattern);
            $keys = array_keys($sub_patterns);
            $spc = count($sub_patterns);
            if ($spc > 4 && $this->pattern_order_offset) {
                $keys = [];
                for ($i = 0; $i < $spc; ++$i) {
                    $keys[$i] = $i + $this->pattern_order_offset;
                    while ($keys[$i] >= $spc) {
                        $keys[$i] -= $spc;
                    }
                }
            }
            foreach ($keys as $i => $key) {
                $sub_pattern = $sub_patterns[$key];
                $sub_pattern['parent_id'] = $id;
                $sub_id = $id.'_'.$key;
                $this->buildIndex($sub_pattern, $sub_id);
                $pattern['patterns'][$i] = $sub_id;
                if ('union' == $type) {
                    $this->index['union_branches'][] = $sub_id;
                }
            }
        }
        $this->index['patterns'][$id] = $pattern;
    }

    public function analyzeIndex($pattern)
    {
        $type = $this->v('type', '', $pattern);
        if (!$type) {
            return false;
        }
        $type = $pattern['type'];
        $id = $pattern['id'];
        /* triple */
        if ('triple' == $type) {
            foreach (['s', 'p', 'o'] as $term) {
                if ('var' == $pattern[$term.'_type']) {
                    $val = $pattern[$term];
                    $this->index['vars'][$val] = array_merge($this->v($val, [], $this->index['vars']), [['table' => $pattern['id'], 'col' => $term]]);
                }
                if ('bnode' == $pattern[$term.'_type']) {
                    $val = $pattern[$term];
                    $this->index['bnodes'][$val] = array_merge($this->v($val, [], $this->index['bnodes']), [['table' => $pattern['id'], 'col' => $term]]);
                }
            }
            $this->index['triple_patterns'][] = $pattern['id'];
            /* joins */
            if ($this->isOptionalPattern($id)) {
                $this->index['left_join'][] = $id;
            } elseif (!$this->index['from']) {
                $this->index['from'][] = $id;
            } elseif (!$this->getJoinInfos($id)) {
                $this->index['from'][] = $id;
            } else {
                $this->index['join'][] = $id;
            }
            /* graph infos, graph vars */
            $this->index['patterns'][$id]['graph_infos'] = $this->getGraphInfos($id);
            foreach ($this->index['patterns'][$id]['graph_infos'] as $info) {
                if ('graph' == $info['type']) {
                    if ($info['var']) {
                        $val = $info['var']['value'];
                        $this->index['graph_vars'][$val] = array_merge($this->v($val, [], $this->index['graph_vars']), [['table' => $id]]);
                    } elseif ($info['uri']) {
                        $val = $info['uri'];
                        $this->index['graph_uris'][$val] = array_merge($this->v($val, [], $this->index['graph_uris']), [['table' => $id]]);
                    }
                }
            }
        }
        $sub_ids = $this->v('patterns', [], $pattern);
        foreach ($sub_ids as $sub_id) {
            $this->analyzeIndex($this->getPattern($sub_id));
        }
    }

    public function getGraphInfos($id)
    {
        $r = [];
        if ($id) {
            $pattern = $this->index['patterns'][$id];
            $type = $pattern['type'];
            /* graph */
            if ('graph' == $type) {
                $r[] = ['type' => 'graph', 'var' => $pattern['var'], 'uri' => $pattern['uri']];
            }
            $p_pattern = $this->index['patterns'][$pattern['parent_id']];
            if (isset($p_pattern['graph_infos'])) {
                return array_merge($p_pattern['graph_infos'], $r);
            }

            return array_merge($this->getGraphInfos($pattern['parent_id']), $r);
        }
        /* FROM / FROM NAMED */
        else {
            if (isset($this->infos['query']['dataset'])) {
                foreach ($this->infos['query']['dataset'] as $set) {
                    $r[] = array_merge(['type' => 'dataset'], $set);
                }
            }
        }

        return $r;
    }

    public function getPattern($id)
    {
        if (is_array($id)) {
            return $id;
        }

        return $this->v($id, [], $this->index['patterns']);
    }

    public function getInitialPattern($id)
    {
        return $this->v($id, [], $this->initial_index['patterns']);
    }

    public function getUnionIndexes($pre_index)
    {
        $r = [];
        $branches = [];
        $min_depth = 1000;
        /* only process branches with minimum depth */
        foreach ($pre_index['union_branches'] as $id) {
            $branches[$id] = count(preg_split('/\_/', $id));
            $min_depth = min($min_depth, $branches[$id]);
        }
        foreach ($branches as $branch_id => $depth) {
            if ($depth == $min_depth) {
                $union_id = preg_replace('/\_[0-9]+$/', '', $branch_id);
                $index = [
                    'keeping' => $branch_id,
                    'union_branches' => [],
                    'patterns' => $pre_index['patterns'],
                ];
                $old_branches = $index['patterns'][$union_id]['patterns'];
                $skip_id = ($old_branches[0] == $branch_id) ? $old_branches[1] : $old_branches[0];
                $index['patterns'][$union_id]['type'] = 'group';
                $index['patterns'][$union_id]['patterns'] = [$branch_id];
                foreach ($index['patterns'] as $pattern_id => $pattern) {
                    if (preg_match('/^'.$skip_id.'/', $pattern_id)) {
                        unset($index['patterns'][$pattern_id]);
                    } elseif ('union' == $pattern['type']) {
                        foreach ($pattern['patterns'] as $sub_union_branch_id) {
                            $index['union_branches'][] = $sub_union_branch_id;
                        }
                    }
                }
                if ($index['union_branches']) {
                    $r = array_merge($r, $this->getUnionIndexes($index));
                } else {
                    $r[] = $index;
                }
            }
        }

        return $r;
    }

    public function isOptionalPattern($id)
    {
        $pattern = $this->getPattern($id);
        if ('optional' == $this->v('type', '', $pattern)) {
            return 1;
        }
        if ('0' == $this->v('parent_id', '0', $pattern)) {
            return 0;
        }

        return $this->isOptionalPattern($pattern['parent_id']);
    }

    public function getOptionalPattern($id)
    {
        $pn = $this->getPattern($id);
        do {
            $pn = $this->getPattern($pn['parent_id']);
        } while ($pn['parent_id'] && ('optional' != $pn['type']));

        return $pn['id'];
    }

    public function sameOptional($id, $id2)
    {
        return $this->getOptionalPattern($id) == $this->getOptionalPattern($id2);
    }

    public function isUnionPattern($id)
    {
        $pattern = $this->getPattern($id);
        if ('union' == $this->v('type', '', $pattern)) {
            return 1;
        }
        if ('0' == $this->v('parent_id', '0', $pattern)) {
            return 0;
        }

        return $this->isUnionPattern($pattern['parent_id']);
    }

    public function getValueTable($col)
    {
        return $this->store->getTablePrefix().(preg_match('/^(s|o)$/', $col) ? $col.'2val' : 'id2val');
    }

    public function getGraphTable()
    {
        return $this->store->getTablePrefix().'g2t';
    }

    public function getQuerySQL()
    {
        $nl = "\n";
        $where_sql = $this->getWHERESQL();  /* pre-fills $index['sub_joins'] $index['constraints'] */
        $order_sql = $this->getORDERSQL();  /* pre-fills $index['sub_joins'] $index['constraints'] */

        return ''.(
            $this->is_union_query
                ? 'SELECT'
                : 'SELECT'.$this->getDistinctSQL()).$nl.
                    $this->getResultVarsSQL().$nl. /* fills $index['sub_joins'] */
                    $this->getFROMSQL().
                    $this->getAllJoinsSQL().
                    $this->getWHERESQL().
                    $this->getGROUPSQL().
                    $this->getORDERSQL().
                    ($this->is_union_query
                        ? ''
                        : $this->getLIMITSQL()
                    ).$nl.'';
    }

    public function getDistinctSQL()
    {
        if ($this->is_union_query) {
            $check = $this->v('distinct', 0, $this->infos['query'])
                || $this->v('reduced', 0, $this->infos['query']);

            return $check ? '' : ' ALL';
        }

        $check = $this->v('distinct', 0, $this->infos['query'])
            || $this->v('reduced', 0, $this->infos['query']);

        return $check ? ' DISTINCT' : '';
    }

    public function getResultVarsSQL()
    {
        $r = '';
        $vars = $this->infos['query']['result_vars'];
        $nl = "\n";
        $added = [];
        foreach ($vars as $var) {
            $var_name = $var['var'];
            $tbl_alias = '';
            if ($tbl_infos = $this->getVarTableInfos($var_name, 0)) {
                $tbl = $tbl_infos['table'];
                $col = $tbl_infos['col'];
                $tbl_alias = $tbl_infos['table_alias'];
            } elseif (1 == $var_name) {/* ASK query */
                $r .= '1 AS `success`';
            } else {
                $this->addError('Result variable "'.$var_name.'" not used in query.');
            }
            if ($tbl_alias) {
                /* aggregate */
                if ($var['aggregate']) {
                    $conv_code = '';
                    if ('count' != strtolower($var['aggregate'])) {
                        $tbl_alias = 'V_'.$tbl.'_'.$col.'.val';
                        $conv_code = '0 + ';
                    }
                    if (!isset($added[$var['alias']])) {
                        $r .= $r ? ','.$nl.'  ' : '  ';
                        $distinct_code = ('count' == strtolower($var['aggregate'])) && $this->v('distinct', 0, $this->infos['query']) ? 'DISTINCT ' : '';
                        $r .= $var['aggregate'].'('.$conv_code.$distinct_code.$tbl_alias.') AS `'.$var['alias'].'`';
                        $added[$var['alias']] = 1;
                    }
                }
                /* normal var */
                else {
                    if (!isset($added[$var_name])) {
                        $r .= $r ? ','.$nl.'  ' : '  ';
                        $r .= $tbl_alias.' AS `'.$var_name.'`';
                        $is_s = ('s' == $col);
                        $is_o = ('o' == $col);
                        if ('NULL' == $tbl_alias) {
                            /* type / add in UNION queries? */
                            if ($is_s || $is_o) {
                                $r .= ', '.$nl.'    NULL AS `'.$var_name.' type`';
                            }
                            /* lang_dt / always add it in UNION queries, the var may be used as s/p/o */
                            if ($is_o || $this->is_union_query) {
                                $r .= ', '.$nl.'    NULL AS `'.$var_name.' lang_dt`';
                            }
                        } else {
                            /* type */
                            if ($is_s || $is_o) {
                                $r .= ', '.$nl.'    '.$tbl_alias.'_type AS `'.$var_name.' type`';
                            }
                            /* lang_dt / always add it in UNION queries, the var may be used as s/p/o */
                            if ($is_o) {
                                $r .= ', '.$nl.'    '.$tbl_alias.'_lang_dt AS `'.$var_name.' lang_dt`';
                            } elseif ($this->is_union_query) {
                                $r .= ', '.$nl.'    NULL AS `'.$var_name.' lang_dt`';
                            }
                        }
                        $added[$var_name] = 1;
                    }
                }
                if (!in_array($tbl_alias, $this->index['sub_joins'])) {
                    $this->index['sub_joins'][] = $tbl_alias;
                }
            }
        }

        return $r ? $r : '1 AS `success`';
    }

    public function getVarTableInfos($var, $ignore_initial_index = 1)
    {
        if ('*' == $var) {
            return ['table' => '', 'col' => '', 'table_alias' => '*'];
        }
        if ($infos = $this->v($var, 0, $this->index['vars'])) {
            $infos[0]['table_alias'] = 'T_'.$infos[0]['table'].'.'.$infos[0]['col'];

            return $infos[0];
        }
        if ($infos = $this->v($var, 0, $this->index['graph_vars'])) {
            $infos[0]['col'] = 'g';
            $infos[0]['table_alias'] = 'G_'.$infos[0]['table'].'.'.$infos[0]['col'];

            return $infos[0];
        }
        if ($this->is_union_query && !$ignore_initial_index) {
            if (($infos = $this->v($var, 0, $this->initial_index['vars'])) || ($infos = $this->v($var, 0, $this->initial_index['graph_vars']))) {
                if (!in_array($var, $this->infos['null_vars'])) {
                    $this->infos['null_vars'][] = $var;
                }
                $infos[0]['table_alias'] = 'NULL';
                $infos[0]['col'] = !isset($infos[0]['col']) ? '' : $infos[0]['col'];

                return $infos[0];
            }
        }

        return 0;
    }

    public function getFROMSQL()
    {
        $from_ids = $this->index['from'];
        $r = '';
        foreach ($from_ids as $from_id) {
            $r .= $r ? ', ' : '';
            $r .= $this->getTripleTable($from_id).' T_'.$from_id;
        }

        return $r ? 'FROM '.$r : '';
    }

    public function getOrderedJoinIDs()
    {
        return array_merge($this->index['from'], $this->index['join'], $this->index['left_join']);
    }

    public function getJoinInfos($id)
    {
        $r = [];
        $tbl_ids = $this->getOrderedJoinIDs();
        $pattern = $this->getPattern($id);
        foreach ($tbl_ids as $tbl_id) {
            $tbl_pattern = $this->getPattern($tbl_id);
            if ($tbl_id != $id) {
                foreach (['s', 'p', 'o'] as $tbl_term) {
                    foreach (['var', 'bnode', 'uri'] as $term_type) {
                        if ($tbl_pattern[$tbl_term.'_type'] == $term_type) {
                            foreach (['s', 'p', 'o'] as $term) {
                                if (($pattern[$term.'_type'] == $term_type) && ($tbl_pattern[$tbl_term] == $pattern[$term])) {
                                    $r[] = ['term' => $term, 'join_tbl' => $tbl_id, 'join_term' => $tbl_term];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $r;
    }

    public function getAllJoinsSQL()
    {
        $js = $this->getJoins();
        $ljs = $this->getLeftJoins();
        $entries = array_merge($js, $ljs);
        $id2code = [];
        foreach ($entries as $entry) {
            if (preg_match('/([^\s]+) ON (.*)/s', $entry, $m)) {
                $id2code[$m[1]] = $entry;
            }
        }
        $deps = [];
        foreach ($id2code as $id => $code) {
            $deps[$id]['rank'] = 0;
            foreach ($id2code as $other_id => $other_code) {
                $deps[$id]['rank'] += ($id != $other_id) && preg_match('/'.$other_id.'/', $code) ? 1 : 0;
                $deps[$id][$other_id] = ($id != $other_id) && preg_match('/'.$other_id.'/', $code) ? 1 : 0;
            }
        }
        $r = '';
        do {
            /* get next 0-rank */
            $next_id = 0;
            foreach ($deps as $id => $infos) {
                if (0 == $infos['rank']) {
                    $next_id = $id;
                    break;
                }
            }
            if ($next_id) {
                $r .= "\n".$id2code[$next_id];
                unset($deps[$next_id]);
                foreach ($deps as $id => $infos) {
                    $deps[$id]['rank'] = 0;
                    unset($deps[$id][$next_id]);
                    foreach ($infos as $k => $v) {
                        if (!in_array($k, ['rank', $next_id])) {
                            $deps[$id]['rank'] += $v;
                            $deps[$id][$k] = $v;
                        }
                    }
                }
            }
        } while ($next_id);
        if ($deps) {
            $this->addError('Not all patterns could be rewritten to SQL JOINs');
        }

        return $r;
    }

    public function getJoins()
    {
        $r = [];
        $nl = "\n";
        foreach ($this->index['join'] as $id) {
            $sub_r = $this->getJoinConditionSQL($id);
            $r[] = 'JOIN '.$this->getTripleTable($id).' T_'.$id.' ON ('.$sub_r.$nl.')';
        }
        foreach (array_merge($this->index['from'], $this->index['join']) as $id) {
            if ($sub_r = $this->getRequiredSubJoinSQL($id)) {
                $r[] = $sub_r;
            }
        }

        return $r;
    }

    public function getLeftJoins()
    {
        $r = [];
        $nl = "\n";
        foreach ($this->index['left_join'] as $id) {
            $sub_r = $this->getJoinConditionSQL($id);
            $r[] = 'LEFT JOIN '.$this->getTripleTable($id).' T_'.$id.' ON ('.$sub_r.$nl.')';
        }
        foreach ($this->index['left_join'] as $id) {
            if ($sub_r = $this->getRequiredSubJoinSQL($id, 'LEFT')) {
                $r[] = $sub_r;
            }
        }

        return $r;
    }

    public function getJoinConditionSQL($id)
    {
        $r = '';
        $nl = "\n";
        $infos = $this->getJoinInfos($id);
        $pattern = $this->getPattern($id);

        $tbl = 'T_'.$id;
        /* core dependency */
        $d_tbls = $this->getDependentJoins($id);
        foreach ($d_tbls as $d_tbl) {
            if (preg_match('/^T_([0-9\_]+)\.[spo]+/', $d_tbl, $m) && ($m[1] != $id)) {
                if ($this->isJoinedBefore($m[1], $id) && !in_array($m[1], array_merge($this->index['from'], $this->index['join']))) {
                    $r .= $r ? $nl.'  AND ' : $nl.'  ';
                    $r .= '('.$d_tbl.' IS NOT NULL)';
                }
                $this->logDependency($id, $d_tbl);
            }
        }
        /* triple-based join info */
        foreach ($infos as $info) {
            if ($this->isJoinedBefore($info['join_tbl'], $id) && $this->joinDependsOn($id, $info['join_tbl'])) {
                $r .= $r ? $nl.'  AND ' : $nl.'  ';
                $r .= '('.$tbl.'.'.$info['term'].' = T_'.$info['join_tbl'].'.'.$info['join_term'].')';
            }
        }
        /* filters etc */
        if ($sub_r = $this->getPatternSQL($pattern, 'join__T_'.$id)) {
            $r .= $r ? $nl.'  AND '.$sub_r : $nl.'  ('.$sub_r.')';
        }

        return $r;
    }

    /**
     * A log of identified table join dependencies in getJoinConditionSQL.
     */
    public function logDependency($id, $tbl)
    {
        if (!isset($this->dependency_log[$id])) {
            $this->dependency_log[$id] = [];
        }
        if (!in_array($tbl, $this->dependency_log[$id])) {
            $this->dependency_log[$id][] = $tbl;
        }
    }

    /**
     * checks whether entries in the dependecy log could perhaps be optimized
     * (triggers re-ordering of patterns.
     */
    public function problematicDependencies()
    {
        foreach ($this->dependency_log as $id => $tbls) {
            if (count($tbls) > 1) {
                return count($tbls);
            }
        }

        return 0;
    }

    public function isJoinedBefore($tbl_1, $tbl_2)
    {
        $tbl_ids = $this->getOrderedJoinIDs();
        foreach ($tbl_ids as $id) {
            if ($id == $tbl_1) {
                return 1;
            }
            if ($id == $tbl_2) {
                return 0;
            }
        }
    }

    public function joinDependsOn($id, $id2)
    {
        if (in_array($id2, array_merge($this->index['from'], $this->index['join']))) {
            return 1;
        }
        $d_tbls = $this->getDependentJoins($id2);
        // echo $id . ' :: ' . $id2 . '=>' . print_r($d_tbls, 1);
        foreach ($d_tbls as $d_tbl) {
            if (preg_match('/^T_'.$id.'\./', $d_tbl)) {
                return 1;
            }
        }

        return 0;
    }

    public function getDependentJoins($id)
    {
        $r = [];
        /* sub joins */
        foreach ($this->index['sub_joins'] as $alias) {
            if (preg_match('/^(T|V|G)_'.$id.'/', $alias)) {
                $r[] = $alias;
            }
        }
        /* siblings in shared optional */
        $o_id = $this->getOptionalPattern($id);
        foreach ($this->index['sub_joins'] as $alias) {
            if (preg_match('/^(T|V|G)_'.$o_id.'/', $alias) && !in_array($alias, $r)) {
                $r[] = $alias;
            }
        }
        foreach ($this->index['left_join'] as $alias) {
            if (preg_match('/^'.$o_id.'/', $alias) && !in_array($alias, $r)) {
                $r[] = 'T_'.$alias.'.s';
            }
        }

        return $r;
    }

    public function getRequiredSubJoinSQL($id, $prefix = '')
    {
        /* id is a triple pattern id. Optional FILTERS and GRAPHs are getting added to the join directly */
        $nl = "\n";
        $r = '';
        foreach ($this->index['sub_joins'] as $alias) {
            if (preg_match('/^V_'.$id.'_([a-z\_]+)\.val$/', $alias, $m)) {
                $col = $m[1];
                $sub_r = '';
                if ($this->isOptionalPattern($id)) {
                    $pattern = $this->getPattern($id);
                    do {
                        $pattern = $this->getPattern($pattern['parent_id']);
                    } while ($pattern['parent_id'] && ('optional' != $pattern['type']));
                    $sub_r = $this->getPatternSQL($pattern, 'sub_join__V_'.$id);
                }
                $sub_r = $sub_r ? $nl.'  AND ('.$sub_r.')' : '';
                /* lang dt only on literals */
                if ('o_lang_dt' == $col) {
                    $sub_sub_r = 'T_'.$id.'.o_type = 2';
                    $sub_r .= $nl.'  AND ('.$sub_sub_r.')';
                }
                $cur_prefix = $prefix ? $prefix.' ' : '';
                if ('g' == $col) {
                    $r .= trim($cur_prefix.'JOIN '.$this->getValueTable($col).' V_'.$id.'_'.$col.' ON ('.$nl.'  (G_'.$id.'.'.$col.' = V_'.$id.'_'.$col.'.id) '.$sub_r.$nl.')');
                } else {
                    $r .= trim($cur_prefix.'JOIN '.$this->getValueTable($col).' V_'.$id.'_'.$col.' ON ('.$nl.'  (T_'.$id.'.'.$col.' = V_'.$id.'_'.$col.'.id) '.$sub_r.$nl.')');
                }
            } elseif (preg_match('/^G_'.$id.'\.g$/', $alias, $m)) {
                $pattern = $this->getPattern($id);
                $sub_r = $this->getPatternSQL($pattern, 'graph_sub_join__G_'.$id);
                $sub_r = $sub_r ? $nl.'  AND '.$sub_r : '';
                /* dataset restrictions */
                $gi = $this->getGraphInfos($id);
                $sub_sub_r = '';
                $added_gts = [];
                foreach ($gi as $set) {
                    if (isset($set['graph']) && !in_array($set['graph'], $added_gts)) {
                        $sub_sub_r .= '' !== $sub_sub_r ? ',' : '';
                        $sub_sub_r .= $this->getTermID($set['graph'], 'g');
                        $added_gts[] = $set['graph'];
                    }
                }
                $sub_r .= ('' !== $sub_sub_r) ? $nl.' AND (G_'.$id.'.g IN ('.$sub_sub_r.'))' : '';
                /* other graph join conditions */
                foreach ($this->index['graph_vars'] as $var => $occurs) {
                    $occur_tbls = [];
                    foreach ($occurs as $occur) {
                        $occur_tbls[] = $occur['table'];
                        if ($occur['table'] == $id) {
                            break;
                        }
                    }
                    foreach ($occur_tbls as $tbl) {
                        if (($tbl != $id) && in_array($id, $occur_tbls) && $this->isJoinedBefore($tbl, $id)) {
                            $sub_r .= $nl.'  AND (G_'.$id.'.g = G_'.$tbl.'.g)';
                        }
                    }
                }
                $cur_prefix = $prefix ? $prefix.' ' : '';
                $r .= trim($cur_prefix.'JOIN '.$this->getGraphTable().' G_'.$id.' ON ('.$nl.'  (T_'.$id.'.t = G_'.$id.'.t)'.$sub_r.$nl.')');
            }
        }

        return $r;
    }

    public function getWHERESQL()
    {
        $r = '';
        $nl = "\n";
        /* standard constraints */
        $sub_r = $this->getPatternSQL($this->getPattern('0'), 'where');
        /* additional constraints */
        foreach ($this->index['from'] as $id) {
            if ($sub_sub_r = $this->getConstraintSQL($id)) {
                $sub_r .= $sub_r ? $nl.' AND '.$sub_sub_r : $sub_sub_r;
            }
        }
        $r .= $sub_r ?: '';
        /* left join dependencies */
        foreach ($this->index['left_join'] as $id) {
            $d_joins = $this->getDependentJoins($id);
            $added = [];
            $d_aliases = [];
            $id_alias = 'T_'.$id.'.s';
            foreach ($d_joins as $alias) {
                if (preg_match('/^(T|V|G)_([0-9\_]+)(_[spo])?\.([a-z\_]+)/', $alias, $m)) {
                    $tbl_type = $m[1];
                    $tbl_pattern_id = $m[2];
                    $suffix = $m[3];
                    /* get rid of dependency permutations and nested optionals */
                    if (($tbl_pattern_id >= $id) && $this->sameOptional($tbl_pattern_id, $id)) {
                        if (!in_array($tbl_type.'_'.$tbl_pattern_id.$suffix, $added)) {
                            $sub_r .= $sub_r ? ' AND ' : '';
                            $sub_r .= $alias.' IS NULL';
                            $d_aliases[] = $alias;
                            $added[] = $tbl_type.'_'.$tbl_pattern_id.$suffix;
                            $id_alias = ($tbl_pattern_id == $id) ? $alias : $id_alias;
                        }
                    }
                }
            }
            /* TODO fix this! */
            if (count($d_aliases) > 2) {
                $sub_r1 = '  /* '.$id_alias.' dependencies */';
                $sub_r2 = '(('.$id_alias.' IS NULL) OR (CONCAT('.implode(', ', $d_aliases).') IS NOT NULL))';
                $r .= $r ? $nl.$sub_r1.$nl.'  AND '.$sub_r2 : $sub_r1.$nl.$sub_r2;
            }
        }

        return $r ? $nl.'WHERE '.$r : '';
    }

    public function addConstraintSQLEntry($id, $sql)
    {
        if (!isset($this->index['constraints'][$id])) {
            $this->index['constraints'][$id] = [];
        }
        if (!in_array($sql, $this->index['constraints'][$id])) {
            $this->index['constraints'][$id][] = $sql;
        }
    }

    public function getConstraintSQL($id)
    {
        $r = '';
        $nl = "\n";
        $constraints = $this->v($id, [], $this->index['constraints']);
        foreach ($constraints as $constraint) {
            $r .= $r ? $nl.'  AND '.$constraint : $constraint;
        }

        return $r;
    }

    public function getPatternSQL($pattern, $context)
    {
        $type = $this->v('type', '', $pattern);
        if (!$type) {
            return '';
        }
        $m = 'get'.ucfirst($type).'PatternSQL';

        return method_exists($this, $m)
            ? $this->$m($pattern, $context)
            : $this->getDefaultPatternSQL($pattern, $context);
    }

    public function getDefaultPatternSQL($pattern, $context)
    {
        $r = '';
        $nl = "\n";
        $sub_ids = $this->v('patterns', [], $pattern);
        foreach ($sub_ids as $sub_id) {
            $sub_r = $this->getPatternSQL($this->getPattern($sub_id), $context);
            $r .= ($r && $sub_r) ? $nl.'  AND ('.$sub_r.')' : ($sub_r ?: '');
        }

        return $r ? $r : '';
    }

    public function getTriplePatternSQL($pattern, $context)
    {
        $r = '';
        $nl = "\n";
        $id = $pattern['id'];
        /* s p o */
        $vars = [];
        foreach (['s', 'p', 'o'] as $term) {
            $sub_r = '';
            $type = $pattern[$term.'_type'];
            if ('uri' == $type) {
                $term_id = $this->getTermID($pattern[$term], $term);
                $sub_r = '(T_'.$id.'.'.$term.' = '.$term_id.') /* '.preg_replace('/[\#\*\>]/', '::', $pattern[$term]).' */';
            } elseif ('literal' == $type) {
                $term_id = $this->getTermID($pattern[$term], $term);
                $sub_r = '(T_'.$id.'.'.$term.' = '.$term_id.') /* '.preg_replace('/[\#\n\*\>]/', ' ', $pattern[$term]).' */';
                if (($lang_dt = $this->v1($term.'_lang', '', $pattern)) || ($lang_dt = $this->v1($term.'_datatype', '', $pattern))) {
                    $lang_dt_id = $this->getTermID($lang_dt);
                    $sub_r .= $nl.'  AND (T_'.$id.'.'.$term.'_lang_dt = '.$lang_dt_id.') /* '.preg_replace('/[\#\*\>]/', '::', $lang_dt).' */';
                }
            } elseif ('var' == $type) {
                $val = $pattern[$term];
                if (isset($vars[$val])) {/* repeated var in pattern */
                    $sub_r = '(T_'.$id.'.'.$term.'=T_'.$id.'.'.$vars[$val].')';
                }
                $vars[$val] = $term;
                if ($infos = $this->v($val, 0, $this->index['graph_vars'])) {/* graph var in triple pattern */
                    $sub_r .= $sub_r ? $nl.'  AND ' : '';
                    $tbl = $infos[0]['table'];
                    $sub_r .= 'G_'.$tbl.'.g = T_'.$id.'.'.$term;
                }
            }
            if ($sub_r) {
                if (preg_match('/^(join)/', $context) || (preg_match('/^where/', $context) && in_array($id, $this->index['from']))) {
                    $r .= $r ? $nl.'  AND '.$sub_r : $sub_r;
                }
            }
        }
        /* g */
        if ($infos = $pattern['graph_infos']) {
            $tbl_alias = 'G_'.$id.'.g';
            if (!in_array($tbl_alias, $this->index['sub_joins'])) {
                $this->index['sub_joins'][] = $tbl_alias;
            }
            $sub_r = ['graph_var' => '', 'graph_uri' => '', 'from' => '', 'from_named' => ''];
            foreach ($infos as $info) {
                $type = $info['type'];
                if ('graph' == $type) {
                    if ($info['uri']) {
                        $term_id = $this->getTermID($info['uri'], 'g');
                        $sub_r['graph_uri'] .= $sub_r['graph_uri'] ? $nl.' AND ' : '';
                        $sub_r['graph_uri'] .= '('.$tbl_alias.' = '.$term_id.') /* '.preg_replace('/[\#\*\>]/', '::', $info['uri']).' */';
                    }
                }
            }
            if ($sub_r['from'] && $sub_r['from_named']) {
                $sub_r['from_named'] = '';
            }
            if (!$sub_r['from'] && !$sub_r['from_named']) {
                $sub_r['graph_var'] = '';
            }
            if (preg_match('/^(graph_sub_join)/', $context)) {
                foreach ($sub_r as $g_type => $g_sql) {
                    if ($g_sql) {
                        $r .= $r ? $nl.'  AND '.$g_sql : $g_sql;
                    }
                }
            }
        }
        /* optional sibling filters? */
        if (preg_match('/^(join|sub_join)/', $context) && $this->isOptionalPattern($id)) {
            $o_pattern = $pattern;
            do {
                $o_pattern = $this->getPattern($o_pattern['parent_id']);
            } while ($o_pattern['parent_id'] && ('optional' != $o_pattern['type']));
            if ($sub_r = $this->getPatternSQL($o_pattern, 'optional_filter'.preg_replace('/^(.*)(__.*)$/', '\\2', $context))) {
                $r .= $r ? $nl.'  AND '.$sub_r : $sub_r;
            }
            /* created constraints */
            if ($sub_r = $this->getConstraintSQL($id)) {
                $r .= $r ? $nl.'  AND '.$sub_r : $sub_r;
            }
        }
        /* result */
        if (preg_match('/^(where)/', $context) && $this->isOptionalPattern($id)) {
            return '';
        }

        return $r;
    }

    public function getFilterPatternSQL($pattern, $context)
    {
        $r = '';
        $id = $pattern['id'];
        $constraint_id = $this->v1('constraint', '', $pattern);
        $constraint = $this->getPattern($constraint_id);
        $constraint_type = $constraint['type'];
        if ('built_in_call' == $constraint_type) {
            $r = $this->getBuiltInCallSQL($constraint, $context);
        } elseif ('expression' == $constraint_type) {
            $r = $this->getExpressionSQL($constraint, $context, '', 'filter');
        } else {
            $m = 'get'.ucfirst($constraint_type).'ExpressionSQL';
            if (method_exists($this, $m)) {
                $r = $this->$m($constraint, $context, '', 'filter');
            }
        }
        if ($this->isOptionalPattern($id) && !preg_match('/^(join|optional_filter)/', $context)) {
            return '';
        }
        /* unconnected vars in FILTERs eval to false */
        $sub_r = $this->hasUnconnectedFilterVars($id);
        if ($sub_r) {
            if ('alias' == $sub_r) {
                if (!in_array($r, $this->index['havings'])) {
                    $this->index['havings'][] = $r;
                }

                return '';
            } elseif (preg_match('/^T([^\s]+\.)g (.*)$/s', $r, $m)) {/* graph filter */
                return 'G'.$m[1].'t '.$m[2];
            } elseif (preg_match('/^\(*V[^\s]+_g\.val .*$/s', $r, $m)) {
                /* graph value filter, @@improveMe */
            } else {
                return 'FALSE';
            }
        }
        /* some really ugly tweaks */
        /* empty language filter: FILTER ( lang(?v) = '' ) */
        $r = preg_replace(
            '/\(\/\* language call \*\/ ([^\s]+) = ""\)/s', '((\\1 = "") OR (\\1 LIKE "%:%"))',
            $r
        );

        return $r;
    }

    /**
     * Checks if vars in the given (filter) pattern are used within the filter's scope.
     */
    public function hasUnconnectedFilterVars($filter_pattern_id)
    {
        $scope_id = $this->getFilterScope($filter_pattern_id);
        $vars = $this->getFilterVars($filter_pattern_id);
        $r = 0;
        foreach ($vars as $var_name) {
            if ($this->isUsedTripleVar($var_name, $scope_id)) {
                continue;
            }
            if ($this->isAliasVar($var_name)) {
                $r = 'alias';
                break;
            }
            $r = 1;
            break;
        }

        return $r;
    }

    /**
     * Returns the given filter pattern's scope (the id of the parent group pattern).
     */
    public function getFilterScope($filter_pattern_id)
    {
        $patterns = $this->initial_index['patterns'];
        $r = '';
        foreach ($patterns as $id => $p) {
            /* the id has to be sub-part of the given filter id */
            if (!preg_match('/^'.$id.'.+/', $filter_pattern_id)) {
                continue;
            }
            /* we are looking for a group or union */
            if (!preg_match('/^(group|union)$/', $p['type'])) {
                continue;
            }
            /* we are looking for the longest/deepest match */
            if (strlen($id) > strlen($r)) {
                $r = $id;
            }
        }

        return $r;
    }

    /**
     * Builds a list of vars used in the given (filter) pattern.
     */
    public function getFilterVars($filter_pattern_id)
    {
        $r = [];
        $patterns = $this->initial_index['patterns'];
        /* find vars in the given filter (i.e. the given id is part of their pattern id) */
        foreach ($patterns as $id => $p) {
            if (!preg_match('/^'.$filter_pattern_id.'.+/', $id)) {
                continue;
            }
            $var_name = '';
            if ('var' == $p['type']) {
                $var_name = $p['value'];
            } elseif (('built_in_call' == $p['type']) && ('bound' == $p['call'])) {
                $var_name = $p['args'][0]['value'];
            }
            if ($var_name && !in_array($var_name, $r)) {
                $r[] = $var_name;
            }
        }

        return $r;
    }

    /**
     * Checks if $var_name appears as result projection alias.
     */
    public function isAliasVar($var_name)
    {
        foreach ($this->infos['query']['result_vars'] as $r_var) {
            if ($r_var['alias'] == $var_name) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * Checks if $var_name is used in a triple pattern in the given scope.
     */
    public function isUsedTripleVar($var_name, $scope_id = '0')
    {
        $patterns = $this->initial_index['patterns'];
        foreach ($patterns as $id => $p) {
            if ('triple' != $p['type']) {
                continue;
            }
            if (!preg_match('/^'.$scope_id.'.+/', $id)) {
                continue;
            }
            foreach (['s', 'p', 'o'] as $term) {
                if ('var' != $p[$term.'_type']) {
                    continue;
                }
                if ($p[$term] == $var_name) {
                    return 1;
                }
            }
        }
    }

    public function getExpressionSQL($pattern, $context, $val_type = '', $parent_type = '')
    {
        $r = '';
        $nl = "\n";
        $type = $this->v1('type', '', $pattern);
        $sub_type = $this->v1('sub_type', $type, $pattern);
        if (preg_match('/^(and|or)$/', $sub_type)) {
            foreach ($pattern['patterns'] as $sub_id) {
                $sub_pattern = $this->getPattern($sub_id);
                $sub_pattern_type = $sub_pattern['type'];
                if ('built_in_call' == $sub_pattern_type) {
                    $sub_r = $this->getBuiltInCallSQL($sub_pattern, $context, '', $parent_type);
                } else {
                    $sub_r = $this->getExpressionSQL($sub_pattern, $context, '', $parent_type);
                }
                if ($sub_r) {
                    $r .= $r ? ' '.strtoupper($sub_type).' ('.$sub_r.')' : '('.$sub_r.')';
                }
            }
        } elseif ('built_in_call' == $sub_type) {
            $r = $this->getBuiltInCallSQL($pattern, $context, $val_type, $parent_type);
        } elseif (preg_match('/literal/', $sub_type)) {
            $r = $this->getLiteralExpressionSQL($pattern, $context, $val_type, $parent_type);
        } elseif ($sub_type) {
            $m = 'get'.ucfirst($sub_type).'ExpressionSQL';
            if (method_exists($this, $m)) {
                $r = $this->$m($pattern, $context, '', $parent_type);
            }
        }
        /* skip expressions that reference non-yet-joined tables */
        if (preg_match('/__(T|V|G)_(.+)$/', $context, $m)) {
            $context_pattern_id = $m[2];
            $context_table_type = $m[1];
            if (preg_match_all('/((T|V|G)(\_[0-9])+)/', $r, $m)) {
                $aliases = $m[1];
                $keep = 1;
                foreach ($aliases as $alias) {
                    if (preg_match('/(T|V|G)_(.*)$/', $alias, $m)) {
                        $tbl_type = $m[1];
                        $tbl = $m[2];
                        if (!$this->isJoinedBefore($tbl, $context_pattern_id)) {
                            $keep = 0;
                        } elseif (($context_pattern_id == $tbl) && preg_match('/(TV)/', $context_table_type.$tbl_type)) {
                            $keep = 0;
                        }
                    }
                }
                $r = $keep ? $r : '';
            }
        }

        return $r ? '('.$r.')' : $r;
    }

    public function detectExpressionValueType($pattern_ids)
    {
        foreach ($pattern_ids as $id) {
            $pattern = $this->getPattern($id);
            $type = $this->v('type', '', $pattern);
            if (('literal' == $type) && isset($pattern['datatype'])) {
                if (in_array($pattern['datatype'], [$this->xsd.'integer', $this->xsd.'float', $this->xsd.'double'])) {
                    return 'numeric';
                }
            }
        }

        return '';
    }

    public function getRelationalExpressionSQL($pattern, $context, $val_type = '', $parent_type = '')
    {
        $r = '';
        $val_type = $this->detectExpressionValueType($pattern['patterns']);
        $op = $pattern['operator'];
        foreach ($pattern['patterns'] as $sub_id) {
            $sub_pattern = $this->getPattern($sub_id);
            $sub_pattern['parent_op'] = $op;
            $sub_type = $sub_pattern['type'];
            $m = ('built_in_call' == $sub_type) ? 'getBuiltInCallSQL' : 'get'.ucfirst($sub_type).'ExpressionSQL';
            $m = str_replace('ExpressionExpression', 'Expression', $m);
            $sub_r = method_exists($this, $m) ? $this->$m($sub_pattern, $context, $val_type, 'relational') : '';
            $r .= $r ? ' '.$op.' '.$sub_r : $sub_r;
        }

        return $r ? '('.$r.')' : $r;
    }

    /**
     * @todo not in use, so remove?
     */
    public function getAdditiveExpressionSQL($pattern, $context, $val_type = '', $parent_type = '')
    {
        $r = '';
        $val_type = $this->detectExpressionValueType($pattern['patterns']);
        foreach ($pattern['patterns'] as $sub_id) {
            $sub_pattern = $this->getPattern($sub_id);
            $sub_type = $this->v('type', '', $sub_pattern);
            $m = ('built_in_call' == $sub_type) ? 'getBuiltInCallSQL' : 'get'.ucfirst($sub_type).'ExpressionSQL';
            $m = str_replace('ExpressionExpression', 'Expression', $m);
            $sub_r = method_exists($this, $m) ? $this->$m($sub_pattern, $context, $val_type, 'additive') : '';
            $r .= $r ? ' '.$sub_r : $sub_r;
        }

        return $r;
    }

    /**
     * @todo not in use, so remove?
     */
    public function getMultiplicativeExpressionSQL($pattern, $context, $val_type = '', $parent_type = '')
    {
        $r = '';
        $val_type = $this->detectExpressionValueType($pattern['patterns']);
        foreach ($pattern['patterns'] as $sub_id) {
            $sub_pattern = $this->getPattern($sub_id);
            $sub_type = $sub_pattern['type'];
            $m = ('built_in_call' == $sub_type) ? 'getBuiltInCallSQL' : 'get'.ucfirst($sub_type).'ExpressionSQL';
            $m = str_replace('ExpressionExpression', 'Expression', $m);
            $sub_r = method_exists($this, $m) ? $this->$m($sub_pattern, $context, $val_type, 'multiplicative') : '';
            $r .= $r ? ' '.$sub_r : $sub_r;
        }

        return $r;
    }

    public function getVarExpressionSQL($pattern, $context, $val_type = '', $parent_type = '')
    {
        $var = $pattern['value'];
        $info = $this->getVarTableInfos($var);

        $tbl = false;
        if (isset($info['table'])) {
            $tbl = $info['table'];
        }

        if (!$tbl) {
            /* might be an aggregate var */
            $vars = $this->infos['query']['result_vars'];
            foreach ($vars as $test_var) {
                if ($test_var['alias'] == $pattern['value']) {
                    return '`'.$pattern['value'].'`';
                }
            }

            return '';
        }
        $col = $info['col'];
        if (('order' == $context) && ('o' == $col)) {
            $tbl_alias = 'T_'.$tbl.'.o_comp';
        } elseif ('sameterm' == $context) {
            $tbl_alias = 'T_'.$tbl.'.'.$col;
        } elseif (
            ('relational' == $parent_type)
            && 'o' == $col
            && preg_match('/[\<\>]/', $this->v('parent_op', '', $pattern))) {
            $tbl_alias = 'T_'.$tbl.'.o_comp';
        } else {
            $tbl_alias = 'V_'.$tbl.'_'.$col.'.val';
            if (!in_array($tbl_alias, $this->index['sub_joins'])) {
                $this->index['sub_joins'][] = $tbl_alias;
            }
        }
        $op = $this->v('operator', '', $pattern);
        if (preg_match('/^(filter|and)/', $parent_type)) {
            if ('!' == $op) {
                $r = '((('.$tbl_alias.' = 0) AND (CONCAT("1", '.$tbl_alias.') != 1))'; /* 0 and no string */
                $r .= ' OR ('.$tbl_alias.' IN ("", "false")))'; /* or "", or "false" */
            } else {
                $r = '(('.$tbl_alias.' != 0)'; /* not null */
                $r .= ' OR ((CONCAT("1", '.$tbl_alias.') = 1) AND ('.$tbl_alias.' NOT IN ("", "false"))))'; /* string, and not "" or "false" */
            }
        } else {
            $r = trim($op.' '.$tbl_alias);
            if ('numeric' == $val_type) {
                if (preg_match('/__(T|V|G)_(.+)$/', $context, $m)) {
                    $context_pattern_id = $m[2];
                    $context_table_type = $m[1];
                } else {
                    $context_pattern_id = $pattern['id'];
                    $context_table_type = 'T';
                }
                if ($this->isJoinedBefore($tbl, $context_pattern_id)) {
                    $add = ($tbl != $context_pattern_id) ? 1 : 0;
                    $add = (!$add && ('V' == $context_table_type)) ? 1 : 0;
                    if ($add) {
                        $this->addConstraintSQLEntry($context_pattern_id, '('.$r.' = "0" OR '.$r.'*1.0 != 0)');
                    }
                }
            }
        }

        return $r;
    }

    public function getUriExpressionSQL($pattern, $context, $val_type = '')
    {
        $val = $pattern['uri'];
        $r = $pattern['operator'];
        $r .= is_numeric($val) ? ' '.$val : ' "'.$this->store->a['db_object']->escape($val).'"';

        return $r;
    }

    public function getLiteralExpressionSQL($pattern, $context, $val_type = '', $parent_type = '')
    {
        $val = $pattern['value'];
        $r = $pattern['operator'];
        if (is_numeric($val) && $this->v('datatype', 0, $pattern)) {
            $r .= ' '.$val;
        } elseif (preg_match('/^(true|false)$/i', $val) && ('http://www.w3.org/2001/XMLSchema#boolean' == $this->v1('datatype', '', $pattern))) {
            $r .= ' '.strtoupper($val);
        } elseif ('regex' == $parent_type) {
            $sub_r = $this->store->a['db_object']->escape($val);
            $r .= ' "'.preg_replace('/\x5c\x5c/', '\\', $sub_r).'"';
        } else {
            $r .= ' "'.$this->store->a['db_object']->escape($val).'"';
        }
        if (($lang_dt = $this->v1('lang', '', $pattern)) || ($lang_dt = $this->v1('datatype', '', $pattern))) {
            /* try table/alias via var in siblings */
            if ($var = $this->findSiblingVarExpression($pattern['id'])) {
                if (isset($this->index['vars'][$var])) {
                    $infos = $this->index['vars'][$var];
                    foreach ($infos as $info) {
                        if ('o' == $info['col']) {
                            $tbl = $info['table'];
                            $term_id = $this->getTermID($lang_dt);
                            if ('!=' != $pattern['operator']) {
                                if (preg_match('/__(T|V|G)_(.+)$/', $context, $m)) {
                                    $context_pattern_id = $m[2];
                                    $context_table_type = $m[1];
                                } elseif ('where' == $context) {
                                    $context_pattern_id = $tbl;
                                } else {
                                    $context_pattern_id = $pattern['id'];
                                }
                                // TODO better dependency check
                                if ($tbl == $context_pattern_id) {
                                    if ($term_id || ('http://www.w3.org/2001/XMLSchema#integer' != $lang_dt)) {
                                        /* skip, if simple int, but no id */
                                        $this->addConstraintSQLEntry($context_pattern_id, 'T_'.$tbl.'.o_lang_dt = '.$term_id.' /* '.preg_replace('/[\#\*\>]/', '::', $lang_dt).' */');
                                    }
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }

        return trim($r);
    }

    public function findSiblingVarExpression($id)
    {
        $pattern = $this->getPattern($id);
        do {
            $pattern = $this->getPattern($pattern['parent_id']);
        } while ($pattern['parent_id'] && ('expression' != $pattern['type']));
        $sub_patterns = $this->v('patterns', [], $pattern);
        foreach ($sub_patterns as $sub_id) {
            $sub_pattern = $this->getPattern($sub_id);
            if ('var' == $sub_pattern['type']) {
                return $sub_pattern['value'];
            }
        }

        return '';
    }

    public function getFunctionExpressionSQL($pattern, $context, $val_type = '', $parent_type = '')
    {
        $fnc_uri = $pattern['uri'];
        $op = $this->v('operator', '', $pattern);
        if ($op) {
            $op .= ' ';
        }
        if ($this->allow_extension_functions) {
            /* mysql functions */
            if (preg_match('/^http\:\/\/web\-semantics\.org\/ns\/mysql\/(.*)$/', $fnc_uri, $m)) {
                $fnc_name = strtoupper($m[1]);
                $sub_r = '';
                foreach ($pattern['args'] as $arg) {
                    $sub_r .= $sub_r ? ', ' : '';
                    $sub_r .= $this->getExpressionSQL($arg, $context, $val_type, $parent_type);
                }

                return $op.$fnc_name.'('.$sub_r.')';
            }
            /* any other: ignore */
        }
        /* simple type conversions */
        if (str_starts_with($fnc_uri, 'http://www.w3.org/2001/XMLSchema#')) {
            return $op.$this->getExpressionSQL($pattern['args'][0], $context, $val_type, $parent_type);
        }

        return '';
    }

    public function getBuiltInCallSQL($pattern, $context)
    {
        $call = $pattern['call'];
        $m = 'get'.ucfirst($call).'CallSQL';
        if (method_exists($this, $m)) {
            return $this->$m($pattern, $context);
        } else {
            $this->addError('Unknown built-in call "'.$call.'"');
        }

        return '';
    }

    public function getBoundCallSQL($pattern, $context)
    {
        $r = '';
        $var = $pattern['args'][0]['value'];
        $info = $this->getVarTableInfos($var);
        if (!$tbl = $info['table']) {
            return '';
        }
        $col = $info['col'];
        $tbl_alias = 'T_'.$tbl.'.'.$col;
        if ('!' == $pattern['operator']) {
            return $tbl_alias.' IS NULL';
        }

        return $tbl_alias.' IS NOT NULL';
    }

    public function getHasTypeCallSQL($pattern, $context, $type)
    {
        $r = '';
        $var = $pattern['args'][0]['value'];
        $info = $this->getVarTableInfos($var);
        if (!$tbl = $info['table']) {
            return '';
        }
        $col = $info['col'];
        $tbl_alias = 'T_'.$tbl.'.'.$col.'_type';

        return $tbl_alias.' '.$this->v('operator', '', $pattern).'= '.$type;
    }

    public function getIsliteralCallSQL($pattern, $context)
    {
        return $this->getHasTypeCallSQL($pattern, $context, 2);
    }

    public function getIsblankCallSQL($pattern, $context)
    {
        return $this->getHasTypeCallSQL($pattern, $context, 1);
    }

    public function getIsiriCallSQL($pattern, $context)
    {
        return $this->getHasTypeCallSQL($pattern, $context, 0);
    }

    public function getIsuriCallSQL($pattern, $context)
    {
        return $this->getHasTypeCallSQL($pattern, $context, 0);
    }

    public function getStrCallSQL($pattern, $context)
    {
        $sub_pattern = $pattern['args'][0];
        $sub_type = $sub_pattern['type'];
        $m = 'get'.ucfirst($sub_type).'ExpressionSQL';
        if (method_exists($this, $m)) {
            return $this->$m($sub_pattern, $context);
        }
    }

    public function getFunctionCallSQL($pattern, $context)
    {
        $f_uri = $pattern['uri'];
        if (preg_match('/(integer|double|float|string)$/', $f_uri)) {/* skip conversions */
            $sub_pattern = $pattern['args'][0];
            $sub_type = $sub_pattern['type'];
            $m = 'get'.ucfirst($sub_type).'ExpressionSQL';
            if (method_exists($this, $m)) {
                return $this->$m($sub_pattern, $context);
            }
        }
    }

    public function getLangDatatypeCallSQL($pattern, $context)
    {
        $r = '';
        if (isset($pattern['patterns'])) { /* proceed with first argument only (assumed as base type for type promotion) */
            $sub_pattern = ['args' => [$pattern['patterns'][0]]];

            return $this->getLangDatatypeCallSQL($sub_pattern, $context);
        }
        if (!isset($pattern['args'])) {
            return 'FALSE';
        }
        $sub_type = $pattern['args'][0]['type'];
        if ('var' != $sub_type) {
            return $this->getLangDatatypeCallSQL($pattern['args'][0], $context);
        }
        $var = $pattern['args'][0]['value'];
        $info = $this->getVarTableInfos($var);
        if (!$tbl = $info['table']) {
            return '';
        }
        $col = 'o_lang_dt';
        $tbl_alias = 'V_'.$tbl.'_'.$col.'.val';
        if (!in_array($tbl_alias, $this->index['sub_joins'])) {
            $this->index['sub_joins'][] = $tbl_alias;
        }
        $op = $this->v('operator', '', $pattern);
        $r = trim($op.' '.$tbl_alias);

        return $r;
    }

    public function getDatatypeCallSQL($pattern, $context)
    {
        return '/* datatype call */ '.$this->getLangDatatypeCallSQL($pattern, $context);
    }

    public function getLangCallSQL($pattern, $context)
    {
        return '/* language call */ '.$this->getLangDatatypeCallSQL($pattern, $context);
    }

    public function getLangmatchesCallSQL($pattern, $context)
    {
        if (2 == count($pattern['args'])) {
            $arg_1 = $pattern['args'][0];
            $arg_2 = $pattern['args'][1];
            $sub_r_1 = $this->getBuiltInCallSQL($arg_1, $context); /* adds value join */
            $sub_r_2 = $this->getExpressionSQL($arg_2, $context);
            $op = $this->v('operator', '', $pattern);
            if (preg_match('/^([\"\'])([^\'\"]+)/', $sub_r_2, $m)) {
                if ('*' == $m[2]) {
                    $r = '!' == $op
                        ? 'NOT ('.$sub_r_1.' REGEXP "^[a-zA-Z\-]+$")' : $sub_r_1.' REGEXP "^[a-zA-Z\-]+$"';
                } else {
                    $r = ('!' == $op) ? $sub_r_1.' NOT LIKE '.$m[1].$m[2].'%'.$m[1] : $sub_r_1.' LIKE '.$m[1].$m[2].'%'.$m[1];
                }
            } else {
                $r = ('!' == $op) ? $sub_r_1.' NOT LIKE CONCAT('.$sub_r_2.', "%")' : $sub_r_1.' LIKE CONCAT('.$sub_r_2.', "%")';
            }

            return $r;
        }

        return '';
    }

    /**
     * @todo not in use, so remove?
     */
    public function getSametermCallSQL($pattern, $context)
    {
        if (2 == count($pattern['args'])) {
            $arg_1 = $pattern['args'][0];
            $arg_2 = $pattern['args'][1];
            $sub_r_1 = $this->getExpressionSQL($arg_1, 'sameterm');
            $sub_r_2 = $this->getExpressionSQL($arg_2, 'sameterm');
            $op = $this->v('operator', '', $pattern);
            $r = $sub_r_1.' '.$op.'= '.$sub_r_2;

            return $r;
        }

        return '';
    }

    public function getRegexCallSQL($pattern, $context)
    {
        $ac = count($pattern['args']);
        if ($ac >= 2) {
            foreach ($pattern['args'] as $i => $arg) {
                $var = 'sub_r_'.($i + 1);
                $$var = $this->getExpressionSQL($arg, $context, '', 'regex');
            }
            $sub_r_3 = (isset($sub_r_3) && preg_match('/[\"\'](.+)[\"\']/', $sub_r_3, $m)) ? strtolower($m[1]) : '';
            $op = ('!' == $this->v('operator', '', $pattern)) ? ' NOT' : '';
            if (!$sub_r_1 || !$sub_r_2) {
                return '';
            }
            $is_simple_search = preg_match('/^[\(\"]+(\^)?([a-z0-9\_\-\s]+)(\$)?[\)\"]+$/is', $sub_r_2, $m);
            $is_simple_search = preg_match('/^[\(\"]+(\^)?([^\\\*\[\]\}\{\(\)\"\'\?\+\.]+)(\$)?[\)\"]+$/is', $sub_r_2, $m);
            $is_o_search = preg_match('/o\.val\)*$/', $sub_r_1);
            /* fulltext search (may have "|") */
            if ($is_simple_search && $is_o_search && !$op && (strlen($m[2]) > 8) && $this->store->hasFulltextIndex()) {
                /* MATCH variations */
                if ($val_parts = preg_split('/\|/', $m[2])) {
                    return 'MATCH('.trim($sub_r_1, '()').') AGAINST("'.implode(' ', $val_parts).'")';
                } else {
                    return 'MATCH('.trim($sub_r_1, '()').') AGAINST("'.$m[2].'")';
                }
            }
            if (preg_match('/\|/', $sub_r_2)) {
                $is_simple_search = 0;
            }
            /* LIKE */
            if ($is_simple_search && ('i' == $sub_r_3)) {
                $sub_r_2 = $m[1] ? $m[2] : '%'.$m[2];
                $sub_r_2 .= isset($m[3]) && $m[3] ? '' : '%';

                return $sub_r_1.$op.' LIKE "'.$sub_r_2.'"';
            }
            /* REGEXP */
            $opt = ('i' == $sub_r_3) ? '' : 'BINARY ';

            return $sub_r_1.$op.' REGEXP '.$opt.$sub_r_2;
        }

        return '';
    }

    public function getGROUPSQL()
    {
        $r = '';
        $nl = "\n";
        $infos = $this->v('group_infos', [], $this->infos['query']);
        foreach ($infos as $info) {
            $var = $info['value'];
            if ($tbl_infos = $this->getVarTableInfos($var, 0)) {
                $tbl_alias = $tbl_infos['table_alias'];
                $r .= $r ? ', ' : 'GROUP BY ';
                $r .= $tbl_alias;
            }
        }
        $hr = '';
        foreach ($this->index['havings'] as $having) {
            $hr .= $hr ? ' AND' : ' HAVING';
            $hr .= '('.$having.')';
        }
        $r .= $hr;

        return $r ? $nl.$r : $r;
    }

    public function getORDERSQL()
    {
        $r = '';
        $nl = "\n";
        $infos = $this->v('order_infos', [], $this->infos['query']);
        foreach ($infos as $info) {
            $type = $info['type'];
            $ms = ['expression' => 'getExpressionSQL', 'built_in_call' => 'getBuiltInCallSQL', 'function_call' => 'getFunctionCallSQL'];
            $m = isset($ms[$type]) ? $ms[$type] : 'get'.ucfirst($type).'ExpressionSQL';
            if (method_exists($this, $m)) {
                $sub_r = '('.$this->$m($info, 'order').')';
                $sub_r .= 'desc' == $this->v('direction', '', $info) ? ' DESC' : '';
                $r .= $r ? ','.$nl.$sub_r : $sub_r;
            }
        }

        return $r ? $nl.'ORDER BY '.$r : '';
    }

    public function getLIMITSQL()
    {
        $r = '';
        $nl = "\n";
        $limit = $this->v('limit', -1, $this->infos['query']);
        $offset = $this->v('offset', -1, $this->infos['query']);
        if (-1 != $limit) {
            $offset = (-1 == $offset) ? 0 : $this->store->a['db_object']->escape($offset);
            $r = 'LIMIT '.$offset.','.$limit;
        } elseif (-1 != $offset) {
            // mysql doesn't support stand-alone offsets
            $r = 'LIMIT '.$this->store->a['db_object']->escape($offset).',999999999999';
        }

        return $r ? $nl.$r : '';
    }

    public function getValueSQL($q_tbl, $q_sql)
    {
        $r = '';
        /* result vars */
        $vars = $this->infos['query']['result_vars'];
        $nl = "\n";
        $v_tbls = ['JOIN' => [], 'LEFT JOIN' => []];
        $vc = 1;
        foreach ($vars as $var) {
            $var_name = $var['var'];
            $r .= $r ? ','.$nl.'  ' : '  ';
            $col = '';
            $tbl = '';
            if ('*' != $var_name) {
                if (in_array($var_name, $this->infos['null_vars'])) {
                    if (isset($this->initial_index['vars'][$var_name])) {
                        $col = $this->initial_index['vars'][$var_name][0]['col'];
                        $tbl = $this->initial_index['vars'][$var_name][0]['table'];
                    }
                    if (isset($this->initial_index['graph_vars'][$var_name])) {
                        $col = 'g';
                        $tbl = $this->initial_index['graph_vars'][$var_name][0]['table'];
                    }
                } elseif (isset($this->index['vars'][$var_name])) {
                    $col = $this->index['vars'][$var_name][0]['col'];
                    $tbl = $this->index['vars'][$var_name][0]['table'];
                }
            }
            if ($var['aggregate']) {
                $r .= 'TMP.`'.$var['alias'].'`';
            } else {
                $join_type = in_array($tbl, array_merge($this->index['from'], $this->index['join'])) ? 'JOIN' : 'LEFT JOIN'; /* val may be NULL */
                $v_tbls[$join_type][] = ['t_col' => $col, 'q_col' => $var_name, 'vc' => $vc];
                $r .= 'V'.$vc.'.val AS `'.$var_name.'`';
                if (in_array($col, ['s', 'o'])) {
                    if (strpos($q_sql, '`'.$var_name.' type`')) {
                        $r .= ', '.$nl.'    TMP.`'.$var_name.' type` AS `'.$var_name.' type`';
                        // $r .= ', ' . $nl . '    CASE TMP.`' . $var_name . ' type` WHEN 2 THEN "literal" WHEN 1 THEN "bnode" ELSE "uri" END AS `' . $var_name . ' type`';
                    } else {
                        $r .= ', '.$nl.'    NULL AS `'.$var_name.' type`';
                    }
                }
                ++$vc;
                if ('o' == $col) {
                    $v_tbls[$join_type][] = ['t_col' => 'id', 'q_col' => $var_name.' lang_dt', 'vc' => $vc];
                    if (strpos($q_sql, '`'.$var_name.' lang_dt`')) {
                        $r .= ', '.$nl.'    V'.$vc.'.val AS `'.$var_name.' lang_dt`';
                        ++$vc;
                    } else {
                        $r .= ', '.$nl.'    NULL AS `'.$var_name.' lang_dt`';
                    }
                }
            }
        }
        if (!$r) {
            $r = '*';
        }
        /* from */
        $r .= $nl.'FROM ('.$q_tbl.' TMP)';
        foreach (['JOIN', 'LEFT JOIN'] as $join_type) {
            foreach ($v_tbls[$join_type] as $v_tbl) {
                $tbl = $this->getValueTable($v_tbl['t_col']);
                $var_name = preg_replace('/^([^\s]+)(.*)$/', '\\1', $v_tbl['q_col']);
                $cur_join_type = in_array($var_name, $this->infos['null_vars']) ? 'LEFT JOIN' : $join_type;
                if (!strpos($q_sql, '`'.$v_tbl['q_col'].'`')) {
                    continue;
                }
                $r .= $nl.' '.$cur_join_type.' '.$tbl.' V'.$v_tbl['vc'].' ON (
            (V'.$v_tbl['vc'].'.id = TMP.`'.$v_tbl['q_col'].'`)
        )';
            }
        }
        /* create pos columns, id needed */
        if ($this->v('order_infos', [], $this->infos['query'])) {
            $r .= $nl.' ORDER BY TMPPOS';
        }

        return 'SELECT'.$nl.$r;
    }
}
