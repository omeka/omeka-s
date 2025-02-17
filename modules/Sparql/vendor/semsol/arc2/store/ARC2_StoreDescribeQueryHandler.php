<?php
/*
@homepage <https://github.com/semsol/arc2>
@license W3C Software License and GPL

class:    ARC2 Store DESCRIBE Query Handler
author:   Benjamin Nowack
version:  2010-11-16
*/

ARC2::inc('StoreSelectQueryHandler');

class ARC2_StoreDescribeQueryHandler extends ARC2_StoreSelectQueryHandler
{
    /**
     * @var array<mixed>
     */
    public array $added_triples;

    /**
     * @var array<mixed>
     */
    public array $described_ids;

    public int $detect_labels;

    /**
     * @var array<mixed>
     */
    public array $ids;

    /**
     * @var array<mixed>
     */
    public array $r;

    public function __construct($a, &$caller)
    {/* caller has to be a store */
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->store = $this->caller;
        $this->detect_labels = $this->v('detect_describe_query_labels', 0, $this->a);
    }

    public function runQuery($infos)
    {
        $ids = $infos['query']['result_uris'];
        if ($vars = $infos['query']['result_vars']) {
            $sub_r = parent::runQuery($infos);
            $rf = $this->v('result_format', '', $infos);
            if (in_array($rf, ['sql', 'structure', 'index'])) {
                return $sub_r;
            }
            $rows = $this->v('rows', [], $sub_r);
            foreach ($rows as $row) {
                foreach ($vars as $info) {
                    $val = isset($row[$info['var']]) ? $row[$info['var']] : '';
                    if ($val && ('literal' != $row[$info['var'].' type']) && !in_array($val, $ids)) {
                        $ids[] = $val;
                    }
                }
            }
        }
        $this->r = [];
        $this->described_ids = [];
        $this->ids = $ids;
        $this->added_triples = [];
        $is_sub_describe = 0;
        while ($this->ids) {
            $id = $this->ids[0];
            $this->described_ids[] = $id;
            if ($this->detect_labels) {
                $q = '
          CONSTRUCT {
            <'.$id.'> ?p ?o .
            ?o ?label_p ?o_label .
            ?o <http://arc.semsol.org/ns/arc#label> ?o_label .
          } WHERE {
            <'.$id.'> ?p ?o .
            OPTIONAL {
              ?o ?label_p ?o_label .
              FILTER REGEX(str(?label_p), "(name|label|title|summary|nick|fn)$", "i")
            }
          }
        ';
            } else {
                $q = '
          CONSTRUCT {
            <'.$id.'> ?p ?o .
          } WHERE {
            <'.$id.'> ?p ?o .
          }
        ';
            }
            $sub_r = $this->store->query($q);
            $sub_index = is_array($sub_r['result']) ? $sub_r['result'] : [];
            $this->mergeSubResults($sub_index, $is_sub_describe);
            $is_sub_describe = 1;
        }

        return $this->r;
    }

    public function mergeSubResults($index, $is_sub_describe = 1)
    {
        foreach ($index as $s => $ps) {
            if (!isset($this->r[$s])) {
                $this->r[$s] = [];
            }
            foreach ($ps as $p => $os) {
                if (!isset($this->r[$s][$p])) {
                    $this->r[$s][$p] = [];
                }
                foreach ($os as $o) {
                    $id = md5($s.' '.$p.' '.serialize($o));
                    if (!isset($this->added_triples[$id])) {
                        if (1 || !$is_sub_describe) {
                            $this->r[$s][$p][] = $o;
                            if (is_array($o) && ('bnode' == $o['type']) && !in_array($o['value'], $this->ids)) {
                                $this->ids[] = $o['value'];
                            }
                        } elseif (!is_array($o) || ('bnode' != $o['type'])) {
                            $this->r[$s][$p][] = $o;
                        }
                        $this->added_triples[$id] = 1;
                    }
                }
            }
        }
        /* adjust ids */
        $ids = $this->ids;
        $this->ids = [];
        foreach ($ids as $id) {
            if (!in_array($id, $this->described_ids)) {
                $this->ids[] = $id;
            }
        }
    }
}
