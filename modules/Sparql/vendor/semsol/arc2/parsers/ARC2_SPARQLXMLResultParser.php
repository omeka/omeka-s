<?php
/*
@homepage <https://github.com/semsol/arc2>
@license W3C Software License and GPL

class:    ARC2 SPARQL Result XML Parser
author:   Benjamin Nowack
version:  2010-11-16
*/

ARC2::inc('LegacyXMLParser');

class ARC2_SPARQLXMLResultParser extends ARC2_LegacyXMLParser
{
    public int $allowCDataNodes;

    /**
     * @var array<mixed>
     */
    public array $nodes;
    public string $srx;
    public string $xml;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {/* reader */
        parent::__init();
        $this->srx = 'http://www.w3.org/2005/sparql-results#';
        $this->nsp[$this->srx] = 'srx';
        $this->allowCDataNodes = 0;
    }

    public function done()
    {
    }

    public function getVariables()
    {
        $r = [];
        foreach ($this->nodes as $node) {
            if ($node['tag'] == $this->srx.'variable') {
                $r[] = $node['a']['name'];
            }
        }

        return $r;
    }

    public function getRows()
    {
        $r = [];
        $index = $this->getNodeIndex();
        foreach ($this->nodes as $node) {
            if ($node['tag'] == $this->srx.'result') {
                $row = [];
                $row_id = $node['id'];
                $bindings = isset($index[$row_id]) ? $index[$row_id] : [];
                foreach ($bindings as $binding) {
                    $row = array_merge($row, $this->getBinding($binding));
                }
                if ($row) {
                    $r[] = $row;
                }
            }
        }

        return $r;
    }

    public function getBinding($node)
    {
        $r = [];
        $index = $this->getNodeIndex();
        $var = $node['a']['name'];
        $term = $index[$node['id']][0];
        $r[$var.' type'] = preg_replace('/^uri$/', 'uri', substr($term['tag'], strlen($this->srx)));
        $r[$var] = ('bnode' == $r[$var.' type']) ? '_:'.$term['cdata'] : $term['cdata'];
        if (isset($term['a']['datatype'])) {
            $r[$var.' datatype'] = $term['a']['datatype'];
        } elseif (isset($term['a'][$this->xml.'lang'])) {
            $r[$var.' lang'] = $term['a'][$this->xml.'lang'];
        }

        return $r;
    }

    public function getBooleanInsertedDeleted()
    {
        foreach ($this->nodes as $node) {
            if ($node['tag'] == $this->srx.'boolean') {
                return ('true' == $node['cdata']) ? ['boolean' => true] : ['boolean' => false];
            } elseif ($node['tag'] == $this->srx.'inserted') {
                return ['inserted' => $node['cdata']];
            } elseif ($node['tag'] == $this->srx.'deleted') {
                return ['deleted' => $node['cdata']];
            } elseif ($node['tag'] == $this->srx.'results') {
                return '';
            }
        }

        return '';
    }

    public function getStructure()
    {
        $r = ['variables' => $this->getVariables(), 'rows' => $this->getRows()];
        /* boolean|inserted|deleted */
        if ($sub_r = $this->getBooleanInsertedDeleted()) {
            foreach ($sub_r as $k => $v) {
                $r[$k] = $v;
            }
        }

        return $r;
    }
}
