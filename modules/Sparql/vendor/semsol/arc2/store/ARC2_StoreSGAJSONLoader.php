<?php
/*
@homepage <https://github.com/semsol/arc2>
@license W3C Software License and GPL

class:    ARC2 Store SG API JSON Loader
author:   Benjamin Nowack
version:  2010-11-16
*/

ARC2::inc('SGAJSONParser');

class ARC2_StoreSGAJSONLoader extends ARC2_SGAJSONParser
{
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

    public function addT($s = '', $p = '', $o = '', $s_type = '', $o_type = '', $o_dt = '', $o_lang = '')
    {
        $this->caller->addT($s, $p, $o, $s_type, $o_type, $o_dt, $o_lang);
        ++$this->t_count;
    }
}
