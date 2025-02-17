<?php
/**
 * ARC2 Graph object.
 *
 * @author Benjamin Nowack <mail@bnowack.de>
 * @license W3C Software License
 *
 * @homepage <https://github.com/semsol/arc2>
 */
ARC2::inc('Class');

class ARC2_Graph extends ARC2_Class
{
    protected $index;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->index = [];
    }

    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function addIndex($index)
    {
        $this->index = ARC2::getMergedIndex($this->index, $index);

        return $this;
    }

    public function addGraph($graph)
    {
        // namespaces
        foreach ($graph->ns as $prefix => $ns) {
            $this->setPrefix($prefix, $ns);
        }
        // index
        $this->addIndex($graph->getIndex());

        return $this;
    }

    public function addRdf($data, $format = null)
    {
        if ('json' == $format) {
            return $this->addIndex(json_decode($data, true));
        } else {// parse any other rdf format
            return $this->addIndex($this->toIndex($data));
        }
    }

    public function hasSubject($s)
    {
        return isset($this->index[$s]);
    }

    public function hasTriple($s, $p, $o)
    {
        if (!is_array($o)) {
            return $this->hasLiteralTriple($s, $p, $o) || $this->hasLinkTriple($s, $p, $o);
        }
        if (!isset($this->index[$s])) {
            return false;
        }
        $p = $this->expandPName($p);
        if (!isset($this->index[$s][$p])) {
            return false;
        }

        return in_array($o, $this->index[$s][$p]);
    }

    public function hasLiteralTriple($s, $p, $o)
    {
        if (!isset($this->index[$s])) {
            return false;
        }
        $p = $this->expandPName($p);
        if (!isset($this->index[$s][$p])) {
            return false;
        }
        $os = $this->getObjects($s, $p, false);
        foreach ($os as $object) {
            if ($object['value'] == $o && 'literal' == $object['type']) {
                return true;
            }
        }

        return false;
    }

    public function hasLinkTriple($s, $p, $o)
    {
        if (!isset($this->index[$s])) {
            return false;
        }
        $p = $this->expandPName($p);
        if (!isset($this->index[$s][$p])) {
            return false;
        }
        $os = $this->getObjects($s, $p, false);
        foreach ($os as $object) {
            if ($object['value'] == $o && ('uri' == $object['type'] || 'bnode' == $object['type'])) {
                return true;
            }
        }

        return false;
    }

    public function addTriple($s, $p, $o, $oType = 'literal')
    {
        $p = $this->expandPName($p);
        if (!is_array($o)) {
            $o = ['value' => $o, 'type' => $oType];
        }
        if ($this->hasTriple($s, $p, $o)) {
            return;
        }
        if (!isset($this->index[$s])) {
            $this->index[$s] = [];
        }
        if (!isset($this->index[$s][$p])) {
            $this->index[$s][$p] = [];
        }
        $this->index[$s][$p][] = $o;

        return $this;
    }

    public function getSubjects($p = null, $o = null)
    {
        if (!$p && !$o) {
            return array_keys($this->index);
        }
        $result = [];
        foreach ($this->index as $s => $ps) {
            foreach ($ps as $predicate => $os) {
                if ($p && $predicate != $p) {
                    continue;
                }
                foreach ($os as $object) {
                    if (!$o) {
                        $result[] = $s;
                        break;
                    } elseif (is_array($o) && $object == $o) {
                        $result[] = $s;
                        break;
                    } elseif ($o && $object['value'] == $o) {
                        $result[] = $s;
                        break;
                    }
                }
            }
        }

        return array_unique($result);
    }

    public function getPredicates($s = null)
    {
        $result = [];
        $index = $s ? ([$s => isset($this->index[$s]) ? $this->index[$s] : []]) : $this->index;
        foreach ($index as $subject => $ps) {
            if ($s && $s != $subject) {
                continue;
            }
            $result = array_merge($result, array_keys($ps));
        }

        return array_unique($result);
    }

    public function getObjects($s, $p, $plain = false)
    {
        if (!isset($this->index[$s])) {
            return [];
        }
        $p = $this->expandPName($p);
        if (!isset($this->index[$s][$p])) {
            return [];
        }
        $os = $this->index[$s][$p];
        if ($plain) {
            array_walk($os, function (&$o) {
                $o = $o['value'];
            });
        }

        return $os;
    }

    public function getObject($s, $p, $plain = false, $default = null)
    {
        $os = $this->getObjects($s, $p, $plain);

        return empty($os) ? $default : $os[0];
    }

    public function getNTriples()
    {
        return parent::toNTriples($this->index, $this->ns);
    }

    public function getTurtle()
    {
        return parent::toTurtle($this->index, $this->ns);
    }

    public function getRDFXML()
    {
        return parent::toRDFXML($this->index, $this->ns);
    }

    public function getJSON()
    {
        return json_encode($this->index);
    }
}
