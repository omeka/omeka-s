<?php
/*
@homepage <https://github.com/semsol/arc2>
@license W3C Software License and GPL

class:    ARC2 RDF Store Helper
author:   Benjamin Nowack
version:  2010-11-16
*/

ARC2::inc('Class');

class ARC2_StoreHelper extends ARC2_Class
{
    public $store;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->store = $this->caller;
    }

    public function changeNamespaceURI($old_uri, $new_uri)
    {
        $id_changes = 0;
        $t_changes = 0;
        /* table lock */
        if ($this->store->getLock()) {
            foreach (['id', 's', 'o'] as $id_col) {
                $tbl = $this->store->getTablePrefix().$id_col.'2val';
                $sql = 'SELECT id, val FROM '.$tbl.' WHERE val LIKE "'.$this->store->a['db_object']->escape($old_uri).'%"';
                $rows = $this->store->a['db_object']->fetchList($sql);

                if (false == is_array($rows)) {
                    continue;
                }
                foreach ($rows as $row) {
                    $new_val = str_replace($old_uri, $new_uri, $row['val']);
                    $new_id = $this->store->getTermID($new_val, $id_col);
                    if (!$new_id) {/* unknown ns uri, overwrite current id value */
                        $sub_sql = 'UPDATE '.$tbl." SET val = '".$this->store->a['db_object']->escape($new_val)."' WHERE id = ".$row['id'];
                        $sub_r = $this->store->a['db_object']->simpleQuery($sub_sql);
                        ++$id_changes;
                    } else {/* replace ids */
                        $t_tbls = $this->store->getTables();
                        foreach ($t_tbls as $t_tbl) {
                            if (preg_match('/^triple/', $t_tbl)) {
                                foreach (['s', 'p', 'o', 'o_lang_dt'] as $t_col) {
                                    $sub_sql = 'UPDATE '.$this->store->getTablePrefix().$t_tbl.' SET '.$t_col.' = '.$new_id.' WHERE '.$t_col.' = '.$row['id'];
                                    $sub_r = $this->store->a['db_object']->simpleQuery($sub_sql);
                                    $t_changes += $this->store->a['db_object']->getAffectedRows();
                                }
                            }
                        }
                    }
                }
            }
            $this->store->releaseLock();
        }

        return ['id_replacements' => $id_changes, 'triple_updates' => $t_changes];
    }
}
