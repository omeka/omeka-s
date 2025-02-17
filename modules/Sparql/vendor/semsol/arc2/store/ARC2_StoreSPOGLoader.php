<?php
/*
@homepage <https://github.com/semsol/arc2>
@license W3C Software License and GPL

class:    ARC2 Store SPOG Loader
author:   Morten H�ybye Frederiksen / Benjamin Nowack
version:  2010-11-16
*/

ARC2::inc('SPOGParser');

class ARC2_StoreSPOGLoader extends ARC2_SPOGParser
{
    public int $t_count = 0;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
    }

    public function addT($s = '', $p = '', $o = '', $s_type = '', $o_type = '', $o_dt = '', $o_lang = '', $g = '')
    {
        if (!($s && $p && $o)) {
            return 0;
        }
        if (!$g) {
            $g = $this->caller->target_graph;
        }
        if ($this->caller->fixed_target_graph) {
            $g = $this->caller->fixed_target_graph;
        }
        $prev_g = $this->caller->target_graph;
        $this->caller->target_graph = $g;
        $this->caller->addT($s, $p, $o, $s_type, $o_type, $o_dt, $o_lang);
        $this->caller->target_graph = $prev_g;
        ++$this->t_count;
    }
}
