<?php
/*
@homepage <https://github.com/semsol/arc2>
@license W3C Software License and GPL

class:    ARC2 RDF Extractor
author:   Benjamin Nowack
version:  2010-11-16
*/

ARC2::inc('Class');

class ARC2_RDFExtractor extends ARC2_Class
{
    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->nodes = $this->caller->getNodes();
        $this->index = $this->caller->getNodeIndex();
        $this->bnode_prefix = $this->v('bnode_prefix', 'arc'.substr(md5(uniqid(rand())), 0, 4).'b', $this->a);
        $this->bnode_id = 0;
        $this->keep_cdata_ws = $this->v('keep_cdata_whitespace', 0, $this->a);
        if (!isset($this->a['ns'])) {
            $this->a['ns'] = ['rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'];
        }
    }

    public function x($re, $v, $options = 'si')
    {
        return ARC2::x($re, $v, $options);
    }

    public function createBnodeID()
    {
        ++$this->bnode_id;

        return '_:'.$this->bnode_prefix.$this->bnode_id;
    }

    public function extractRDF()
    {
    }

    public function addTs($ts)
    {
        foreach ($ts as $t) {
            $this->caller->addT($t);
        }
    }

    public function addT($t)
    {
        return $this->caller->addT($t);
    }

    public function getSubNodes($n)
    {
        return $this->v($n['id'], [], $this->index);
    }

    public function getParentNode($n)
    {
        return isset($this->nodes[$n['p_id']]) ? $this->nodes[$n['p_id']] : 0;
    }

    public function getSubNodesByClass($n, $cls, $skip_self = 0)
    {
        if (!$skip_self && $this->hasClass($n, $cls)) {
            return [$n];
        }
        $r = [];
        $sns = $this->getSubNodes($n);
        foreach ($sns as $sn) {
            if ($sub_r = $this->getSubNodesByClass($sn, $cls, 0)) {
                $r = array_merge($r, $sub_r);
            }
        }

        return $r;
    }

    public function getSubNodeByClass($n, $cls, $skip_self = 0)
    {
        if (!$skip_self && $this->hasClass($n, $cls)) {
            return $n;
        }
        $sns = $this->getSubNodes($n);
        foreach ($sns as $sn) {
            if ($sub_r = $this->getSubNodeByClass($sn, $cls, 0)) {
                return $sub_r;
            }
        }

        return 0;
    }

    public function getParentNodeByClass($n, $cls, $skip_self = 0)
    {
        if (!$skip_self && $this->hasClass($n, $cls)) {
            return $n;
        }
        if ($pn = $this->getParentNode($n)) {
            if ($sub_r = $this->getParentNodeByClass($pn, $cls, 0)) {
                return $sub_r;
            }
        }

        return 0;
    }

    public function hasAttribute($a, $n, $v)
    {
        $vs = is_array($v) ? $v : [$v];
        $a_vs = $this->v($a.' m', [], $n['a']);

        return array_intersect($vs, $a_vs) ? 1 : 0;
    }

    public function hasClass($n, $v)
    {
        return $this->hasAttribute('class', $n, $v);
    }

    public function hasRel($n, $v)
    {
        return $this->hasAttribute('rel', $n, $v);
    }

    public function getDocBase()
    {
        $root_node = $this->getRootNode();
        $r = $root_node['doc_base'];
        foreach ($this->getSubNodes($root_node) as $root_child) {
            if ('head' == $root_child['tag']) {
                foreach ($this->getSubNodes($root_child) as $head_child) {
                    if ('base' == $head_child['tag']) {
                        $r = $head_child['a']['href'];
                        break;
                    }
                }
            }
        }

        return $r;
    }

    public function getPlainContent($n, $trim = 1, $use_img_alt = 1)
    {
        if ('comment' == $n['tag']) {
            $r = '';
        } elseif ('cdata' == $n['tag']) {
            $r = $n['a']['value'];
        } elseif (trim($this->v('cdata', '', $n))) {
            $r = $n['cdata'];
            $sub_nodes = $this->getSubNodes($n);
            foreach ($sub_nodes as $sub_n) {
                $r .= $this->getPlainContent($sub_n, 0, $use_img_alt);
            }
        } elseif (('img' == $n['tag']) && $use_img_alt && isset($n['a']['alt'])) {
            $r = $n['a']['alt'];
        } else {
            $r = '';
            $sub_nodes = $this->getSubNodes($n);
            foreach ($sub_nodes as $sub_n) {
                $r .= $this->getPlainContent($sub_n, 0, $use_img_alt);
            }
        }
        $r = preg_replace('/\s/s', ' ', $r);
        $r = preg_replace('/\s\s*/s', ' ', $r);

        return $trim ? trim($r) : $r;
    }

    public function getContent($n, $outer = 0, $trim = 1)
    {
        // echo '<pre>' . htmlspecialchars(print_r($n, 1)) . '</pre>';
        if ('comment' == $n['tag']) {
            $r = '<!-- '.$n['a']['value'].' -->';
        } elseif ('cdata' == $n['tag']) {
            $r = $n['a']['value'];
        } else {
            $r = '';
            if ($outer) {
                $r .= '<'.$n['tag'];
                asort($n['a']);
                if (isset($n['a']['xmlns']) && $n['a']['xmlns']['']) {
                    $r .= ' xmlns="'.$n['a']['xmlns'][''].'"';
                }
                foreach ($n['a'] as $a => $val) {
                    if (!is_array($val) && isset($n['a'][$a.' uri'])) {
                        $val = $n['a'][$a.' uri'];
                    }
                    $r .= preg_match('/^[^\s]+$/', $a) && !is_array($val) ? ' '.$a.'="'.addslashes($val).'"' : '';
                }
                $r .= $n['empty'] ? '/>' : '>';
            }
            if (!$n['empty']) {
                $r .= $this->v('cdata', '', $n);
                $sub_nodes = $this->getSubNodes($n);
                foreach ($sub_nodes as $sub_n) {
                    $r .= $this->getContent($sub_n, 1, 0);
                }
                if ($outer) {
                    $r .= '</'.$n['tag'].'>';
                }
            }
        }

        return ($trim && !$this->keep_cdata_ws) ? trim($r) : $r;
    }

    public function getDocID($n)
    {
        $id = $n['id'];
        $k = 'doc_'.$id;
        if (!isset($this->caller->cache[$k])) {
            $this->caller->cache[$k] = $n['doc_url'];
        }

        return $this->caller->cache[$k];
    }

    public function getDocOwnerID($n)
    {
        return '_:owner_of_'.$this->normalize($this->getDocID($n));
    }

    public function normalize($v)
    {
        $v = preg_replace('/[\W\s]+/is', '_', strip_tags(strtolower($v)));
        $v = preg_replace('/http/', '', $v);
        $v = preg_replace('/[\_]+/', '_', $v);
        // $v = substr($v, 0, 30);
        $v = trim($v, '_');

        return $v;
    }
}
