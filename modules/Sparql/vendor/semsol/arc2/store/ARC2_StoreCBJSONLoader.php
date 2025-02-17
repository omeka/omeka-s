<?php
/**
 * ARC2 Store CrunchBase API JSON Loader.
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 *
 * @version 2010-11-16
 */
ARC2::inc('CBJSONParser');

class ARC2_StoreCBJSONLoader extends ARC2_CBJSONParser
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
        $o = $this->toUTF8($o);
        $this->caller->addT($s, $p, $o, $s_type, $o_type, $o_dt, $o_lang);
        ++$this->t_count;
    }
}
