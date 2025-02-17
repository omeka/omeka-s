<?php
/*
@homepage <https://github.com/semsol/arc2>
@license W3C Software License and GPL

class:    ARC2 Store Turtle Loader
author:   Benjamin Nowack
version:  2010-11-16
*/

ARC2::inc('TurtleParser');

class ARC2_StoreTurtleLoader extends ARC2_TurtleParser
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

    public function addT($t)
    {
        $this->caller->addT(
            $t['s'],
            $t['p'],
            $t['o'],
            $t['s_type'],
            $t['o_type'],
            $t['o_datatype'],
            $t['o_lang']
        );

        ++$this->t_count;
    }
}
