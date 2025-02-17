<?php
/*
@homepage <https://github.com/semsol/arc2>
@license W3C Software License and GPL

class:    ARC2 Extractor
author:   Benjamin Nowack
version:  2010-11-16
*/

ARC2::inc('RDFExtractor');

class ARC2_TwitterProfilePicExtractor extends ARC2_RDFExtractor
{
    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->a['ns']['foaf'] = 'http://xmlns.com/foaf/0.1/';
        $this->a['ns']['mf'] = 'http://poshrdf.org/ns/mf#';
    }

    public function extractRDF()
    {
        $t_vals = [];
        $t = '';
        foreach ($this->nodes as $n) {
            if (isset($n['tag']) && ('img' == $n['tag']) && ('profile-image' == $this->v('id', '', $n['a']))) {
                $t_vals['vcard_id'] = $this->getDocID($n).'#resource(side/1/2/1)';
                $t .= '?vcard_id mf:photo <'.$n['a']['src'].'> . ';
                break;
            }
        }
        if ($t) {
            $doc = $this->getFilledTemplate($t, $t_vals, $n['doc_base']);
            $this->addTs(ARC2::getTriplesFromIndex($doc));
        }
    }
}
