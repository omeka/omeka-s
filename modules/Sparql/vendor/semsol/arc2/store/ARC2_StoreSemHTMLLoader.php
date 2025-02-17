<?php
/*
@homepage <https://github.com/semsol/arc2>
@license W3C Software License and GPL

class:    ARC2 Store SemHTML Loader
author:   Benjamin Nowack
version:  2010-11-16
*/

ARC2::inc('SemHTMLParser');

class ARC2_StoreSemHTMLLoader extends ARC2_SemHTMLParser
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

    public function done()
    {
        $this->extractRDF();
    }

    public function addT($t)
    {
        $this->caller->addT($t['s'], $t['p'], $t['o'], $t['s_type'], $t['o_type'], $t['o_datatype'], $t['o_lang']);
        ++$this->t_count;
    }
}
