<?php
/**
 * ARC2 SPARQL ASK query handler.
 *
 * @author Benjamin Nowack
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 *
 * @version 2010-11-16
 */
ARC2::inc('StoreSelectQueryHandler');

class ARC2_StoreAskQueryHandler extends ARC2_StoreSelectQueryHandler
{
    public function __construct($a, &$caller)
    {/* caller has to be a store */
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->store = $this->caller;
    }

    public function runQuery($infos)
    {
        $infos['query']['limit'] = 1;
        $this->infos = $infos;
        $this->buildResultVars();

        return parent::runQuery($this->infos);
    }

    public function buildResultVars()
    {
        $this->infos['query']['result_vars'][] = ['var' => '1', 'aggregate' => '', 'alias' => 'success'];
    }

    public function getFinalQueryResult($q_sql, $tmp_tbl)
    {
        $row = $this->store->a['db_object']->fetchRow('SELECT success FROM '.$tmp_tbl);
        $r = isset($row['success']) ? $row['success'] : 0;

        return $r ? true : false;
    }
}
