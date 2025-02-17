<?php
/**
 * ARC2 Resource object
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 *
 * @version 2011-01-19
 */
ARC2::inc('Class');

class ARC2_Resource extends ARC2_Class
{
    public string $uri;
    public $index;
    public $fetched;
    public $store;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->uri = '';
        $this->index = [];
        $this->fetched = [];
        $this->store = '';
    }

    public function setURI($uri)
    {
        $this->uri = $uri;
    }

    public function setIndex($index)
    {
        $this->index = $index;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function setProps($props, $s = '')
    {
        if (!$s) {
            $s = $this->uri;
        }
        $this->index[$s] = $props;
    }

    public function setProp($p, $os, $s = '')
    {
        if (!$s) {
            $s = $this->uri;
        }
        /* single plain value */
        if (!is_array($os)) {
            $os = ['value' => $os, 'type' => 'literal'];
        }
        /* single array value */
        if (isset($os['value'])) {
            $os = [$os];
        }
        /* list of values */
        foreach ($os as $i => $o) {
            if (!is_array($o)) {
                $os[$i] = ['value' => $o, 'type' => 'literal'];
            }
        }
        $this->index[$s][$this->expandPName($p)] = $os;
    }

    /* add a relation to a URI. Allows for instance $res->setRel('rdf:type', 'doap:Project') */
    public function setRel($p, $r, $s = '')
    {
        if (!is_array($r)) {
            $uri = [
                  'type' => 'uri',
                  'value' => $this->expandPName($r)];
            $this->setProp($p, $uri, $s);
        } else {
            if (!$s) {
                $s = $this->uri;
            }
            foreach ($r as $i => $x) {
                if (!is_array($x)) {
                    $uri = [
                          'type' => 'uri',
                          'value' => $this->expandPName($x)];
                    $r[$i] = $uri;
                }
            }
            $this->index[$s][$this->expandPName($p)] = $r;
        }
    }

    /* Specialize setProp to set an xsd:dateTime typed literal. Example : $res->setPropXSDdateTime('dcterms:created', date('c')) */
    public function setPropXSDdateTime($p, $dt, $s = '')
    {
        $datecreated = ['value' => $dt,
            'type' => 'literal',
            'datatype' => 'http://www.w3.org/2001/XMLSchema#dateTime'];
        $this->setProp($p, $datecreated, $s);
    }

    public function setStore($store)
    {
        $this->store = $store;
    }

    public function fetchData($uri = '')
    {
        if (!$uri) {
            $uri = $this->uri;
        }
        if (!$uri) {
            return 0;
        }
        if (in_array($uri, $this->fetched)) {
            return 0;
        }
        $this->index[$uri] = [];
        if ($this->store) {
            $index = $this->store->query('CONSTRUCT { <'.$uri.'> ?p ?o . } WHERE { <'.$uri.'> ?p ?o . } ', 'raw');
        } else {
            $index = $this->toIndex($uri);
        }
        $this->index = ARC2::getMergedIndex($this->index, $index);
        $this->fetched[] = $uri;
    }

    public function getProps($p = '', $s = '')
    {
        if (!$s) {
            $s = $this->uri;
        }
        if (!$s) {
            return [];
        }
        if (!isset($this->index[$s])) {
            $this->fetchData($s);
        }
        if (!$p) {
            return $this->index[$s];
        }

        return $this->v($this->expandPName($p), [], $this->index[$s]);
    }

    public function getProp($p, $s = '')
    {
        $props = $this->getProps($p, $s);

        return $props ? $props[0] : '';
    }

    public function getPropValue($p, $s = '')
    {
        $prop = $this->getProp($p, $s);

        return $prop ? $prop['value'] : '';
    }

    public function getPropValues($p, $s = '')
    {
        $r = [];
        $props = $this->getProps($p, $s);
        foreach ($props as $prop) {
            $r[] = $prop['value'];
        }

        return $r;
    }

    public function hasPropValue($p, $o, $s = '')
    {
        $props = $this->getProps($p, $s);
        $o = $this->expandPName($o);
        foreach ($props as $prop) {
            if ($prop['value'] == $o) {
                return 1;
            }
        }

        return 0;
    }
}
