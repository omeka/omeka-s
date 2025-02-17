<?php
/*
@homepage <https://github.com/semsol/arc2>
@license W3C Software License and GPL

class:    ARC2 foaf:openid Extractor
author:   Benjamin Nowack
version:  2010-11-16
*/

ARC2::inc('RDFExtractor');

class ARC2_OpenidExtractor extends ARC2_RDFExtractor
{
    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->a['ns']['foaf'] = 'http://xmlns.com/foaf/0.1/';
    }

    public function extractRDF()
    {
        $t_vals = [];
        $t = '';
        foreach ($this->nodes as $n) {
            if (isset($n['tag']) && 'link' == $n['tag']) {
                $m = 'extract'.ucfirst($n['tag']);
                list($t_vals, $t) = $this->$m($n, $t_vals, $t);
            }
        }
        if ($t) {
            $doc = $this->getFilledTemplate($t, $t_vals, $n['doc_base']);
            $this->addTs(ARC2::getTriplesFromIndex($doc));
        }
    }

    public function extractLink($n, $t_vals, $t)
    {
        if ($this->hasRel($n, 'openid.server')) {
            if ($href = $this->v('href uri', '', $n['a'])) {
                $t_vals['doc_owner'] = $this->getDocOwnerID($n);
                $t_vals['doc_id'] = $this->getDocID($n);
                $t .= '?doc_owner foaf:homepage ?doc_id ; foaf:openid ?doc_id . ';
            }
        }
        if ($this->hasRel($n, 'openid.delegate')) {
            if ($href = $this->v('href uri', '', $n['a'])) {
                $t_vals['doc_owner'] = $this->getDocOwnerID($n);
                $t .= '?doc_owner foaf:homepage <'.$href.'> ; foaf:openid <'.$href.'> . ';
            }
        }

        return [$t_vals, $t];
    }
}
