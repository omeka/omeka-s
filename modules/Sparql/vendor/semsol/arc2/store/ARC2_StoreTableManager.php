<?php
/**
 * ARC2 RDF Store Table Manager.
 *
 * @license   W3C Software License and GPL
 * @author    Benjamin Nowack
 *
 * @version   2010-11-16
 */
ARC2::inc('Store');

/**
 * @todo move its functionality to a class in src/ARC2/Store/TableManager
 */
class ARC2_StoreTableManager extends ARC2_Store
{
    protected string $engine_type;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->engine_type = $this->v('store_engine_type', 'InnoDB', $this->a);
    }

    public function getTableOptionsCode()
    {
        $r = 'ENGINE='.$this->engine_type;
        $r .= ' CHARACTER SET utf8';
        $r .= ' COLLATE utf8_unicode_ci';
        $r .= ' DELAY_KEY_WRITE = 1';

        return $r;
    }

    public function createTables()
    {
        if (!$this->createTripleTable()) {
            return $this->addError('Could not create "triple" table ('.$this->a['db_object']->getErrorMessage().').');
        }
        if (!$this->createG2TTable()) {
            return $this->addError('Could not create "g2t" table ('.$this->a['db_object']->getErrorMessage().').');
        }
        if (!$this->createID2ValTable()) {
            return $this->addError('Could not create "id2val" table ('.$this->a['db_object']->getErrorMessage().').');
        }
        if (!$this->createS2ValTable()) {
            return $this->addError('Could not create "s2val" table ('.$this->a['db_object']->getErrorMessage().').');
        }
        if (!$this->createO2ValTable()) {
            return $this->addError('Could not create "o2val" table ('.$this->a['db_object']->getErrorMessage().').');
        }
        if (!$this->createSettingTable()) {
            return $this->addError('Could not create "setting" table ('.$this->a['db_object']->getErrorMessage().').');
        }

        return 1;
    }

    public function createTripleTable($suffix = 'triple')
    {
        /* keep in sync with merge def in StoreQueryHandler ! */
        $indexes = $this->v('store_indexes', ['sp (s,p)', 'os (o,s)', 'po (p,o)'], $this->a);
        $index_code = $indexes ? 'KEY '.implode(', KEY ', $indexes).', ' : '';
        $sql = '
      CREATE TABLE IF NOT EXISTS '.$this->getTablePrefix().$suffix.' (
        t mediumint UNSIGNED NOT NULL,
        s mediumint UNSIGNED NOT NULL,
        p mediumint UNSIGNED NOT NULL,
        o mediumint UNSIGNED NOT NULL,
        o_lang_dt mediumint UNSIGNED NOT NULL,
        o_comp char(35) NOT NULL,                   /* normalized value for ORDER BY operations */
        s_type tinyint(1) NOT NULL default 0,       /* uri/bnode => 0/1 */
        o_type tinyint(1) NOT NULL default 0,       /* uri/bnode/literal => 0/1/2 */
        misc tinyint(1) NOT NULL default 0,         /* temporary flags */
        UNIQUE KEY (t), '.$index_code.' KEY (misc)
      ) '.$this->getTableOptionsCode().'
    ';

        return $this->a['db_object']->simpleQuery($sql);
    }

    public function extendTripleTableColumns($suffix = 'triple')
    {
        $sql = '
      ALTER TABLE '.$this->getTablePrefix().$suffix.'
      MODIFY t int(10) UNSIGNED NOT NULL,
      MODIFY s int(10) UNSIGNED NOT NULL,
      MODIFY p int(10) UNSIGNED NOT NULL,
      MODIFY o int(10) UNSIGNED NOT NULL,
      MODIFY o_lang_dt int(10) UNSIGNED NOT NULL
    ';

        return $this->a['db_object']->simpleQuery($sql);
    }

    public function createG2TTable()
    {
        $sql = '
      CREATE TABLE IF NOT EXISTS '.$this->getTablePrefix().'g2t (
        g mediumint UNSIGNED NOT NULL,
        t mediumint UNSIGNED NOT NULL,
        UNIQUE KEY gt (g,t), KEY tg (t,g)
      ) '.$this->getTableOptionsCode().'
    ';

        return $this->a['db_object']->simpleQuery($sql);
    }

    public function extendG2tTableColumns($suffix = 'g2t')
    {
        $sql = '
      ALTER TABLE '.$this->getTablePrefix().$suffix.'
      MODIFY g int(10) UNSIGNED NOT NULL,
      MODIFY t int(10) UNSIGNED NOT NULL
    ';

        return $this->a['db_object']->simpleQuery($sql);
    }

    public function createID2ValTable()
    {
        $sql = '
      CREATE TABLE IF NOT EXISTS '.$this->getTablePrefix().'id2val (
        id mediumint UNSIGNED NOT NULL AUTO_INCREMENT,
        misc tinyint(1) NOT NULL default 0,
        val text NOT NULL,
        val_type tinyint(1) NOT NULL default 0,     /* uri/bnode/literal => 0/1/2 */
        PRIMARY KEY (`id`),
        UNIQUE KEY (id,val_type),
        KEY v (val(64))
      ) '.$this->getTableOptionsCode().'
    ';

        return $this->a['db_object']->simpleQuery($sql);
    }

    public function extendId2valTableColumns($suffix = 'id2val')
    {
        $sql = '
      ALTER TABLE '.$this->getTablePrefix().$suffix.'
      MODIFY id int(10) UNSIGNED NOT NULL
    ';

        return $this->a['db_object']->simpleQuery($sql);
    }

    public function createS2ValTable()
    {
        // $indexes = 'UNIQUE KEY (id), KEY vh (val_hash), KEY v (val(64))';
        $indexes = 'UNIQUE KEY (id), KEY vh (val_hash)';
        $sql = '
      CREATE TABLE IF NOT EXISTS '.$this->getTablePrefix().'s2val (
        id mediumint UNSIGNED NOT NULL,
        misc tinyint(1) NOT NULL default 0,
        val_hash char(32) NOT NULL,
        val text NOT NULL,
        '.$indexes.'
      ) '.$this->getTableOptionsCode().'
    ';

        return $this->a['db_object']->simpleQuery($sql);
    }

    public function extendS2valTableColumns($suffix = 's2val')
    {
        $sql = '
      ALTER TABLE '.$this->getTablePrefix().$suffix.'
      MODIFY id int(10) UNSIGNED NOT NULL
    ';

        return $this->a['db_object']->simpleQuery($sql);
    }

    public function createO2ValTable()
    {
        /* object value index, e.g. "KEY v (val(64))" and/or "FULLTEXT KEY vft (val)" */
        $val_index = $this->v('store_object_index', 'KEY v (val(64))', $this->a);
        if ($val_index) {
            $val_index = ', '.ltrim($val_index, ',');
        }
        $sql = '
      CREATE TABLE IF NOT EXISTS '.$this->getTablePrefix().'o2val (
        id mediumint UNSIGNED NOT NULL,
        misc tinyint(1) NOT NULL default 0,
        val_hash char(32) NOT NULL,
        val text NOT NULL,
        UNIQUE KEY (id), KEY vh (val_hash)'.$val_index.'
      ) '.$this->getTableOptionsCode().'
    ';

        return $this->a['db_object']->simpleQuery($sql);
    }

    public function extendO2valTableColumns($suffix = 'o2val')
    {
        $sql = '
      ALTER TABLE '.$this->getTablePrefix().$suffix.'
      MODIFY id int(10) UNSIGNED NOT NULL
    ';

        return $this->a['db_object']->simpleQuery($sql);
    }

    public function createSettingTable()
    {
        $sql = '
      CREATE TABLE IF NOT EXISTS '.$this->getTablePrefix().'setting (
        k char(32) NOT NULL,
        val text NOT NULL,
        UNIQUE KEY (k)
      ) '.$this->getTableOptionsCode().'
    ';

        return $this->a['db_object']->simpleQuery($sql);
    }

    public function extendColumns()
    {
        $tbls = $this->getTables();
        foreach ($tbls as $suffix) {
            if (preg_match('/^(triple|g2t|id2val|s2val|o2val)/', $suffix, $m)) {
                $mthd = 'extend'.ucfirst($m[1]).'TableColumns';
                $this->$mthd($suffix);
            }
        }
    }

    public function splitTables()
    {
        $old_ps = $this->getSetting('split_predicates', []);
        $new_ps = $this->retrieveSplitPredicates();
        $add_ps = array_diff($new_ps, $old_ps);
        $del_ps = array_diff($old_ps, $new_ps);
        $final_ps = [];
        foreach ($del_ps as $p) {
            if (!$this->unsplitPredicate($p)) {
                $final_ps[] = $p;
            }
        }
        foreach ($add_ps as $p) {
            if ($this->splitPredicate($p)) {
                $final_ps[] = $p;
            }
        }
        $this->setSetting('split_predicates', $new_ps);
    }

    public function unsplitPredicate($p)
    {
        $suffix = 'triple_'.abs(crc32($p));
        $old_tbl = $this->getTablePrefix().$suffix;
        $new_tbl = $this->getTablePrefix().'triple';
        $p_id = $this->getTermID($p, 'p');

        $sqlHead = 'INSERT IGNORE INTO ';

        $sql = $sqlHead.$new_tbl.' SELECT * FROM '.$old_tbl.' WHERE '.$old_tbl.'.p = '.$p_id;
        if ($this->a['db_object']->simpleQuery($sql)) {
            $this->a['db_object']->simpleQuery('DROP TABLE '.$old_tbl);

            return 1;
        } else {
            return 0;
        }
    }

    public function splitPredicate($p)
    {
        $suffix = 'triple_'.abs(crc32($p));
        $this->createTripleTable($suffix);
        $old_tbl = $this->getTablePrefix().'triple';
        $new_tbl = $this->getTablePrefix().$suffix;
        $p_id = $this->getTermID($p, 'p');

        $sqlHead = 'INSERT IGNORE INTO ';

        $sql = $sqlHead.$new_tbl.'SELECT * FROM '.$old_tbl.' WHERE '.$old_tbl.'.p = '.$p_id;
        if ($this->a['db_object']->simpleQuery($sql)) {
            $this->a['db_object']->simpleQuery('DELETE FROM '.$old_tbl.' WHERE '.$old_tbl.'.p = '.$p_id);

            return 1;
        } else {
            $this->a['db_object']->simpleQuery('DROP TABLE '.$new_tbl);

            return 0;
        }
    }

    public function retrieveSplitPredicates()
    {
        $r = $this->split_predicates;
        $limit = $this->max_split_tables - count($r);
        $q = 'SELECT ?p COUNT(?p) AS ?pc WHERE { ?s ?p ?o } GROUP BY ?p ORDER BY DESC(?pc) LIMIT '.$limit;
        $rows = $this->query($q, 'rows');
        foreach ($rows as $row) {
            $r[] = $row['p'];
        }

        return $r;
    }
}
