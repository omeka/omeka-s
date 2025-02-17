<?php
/**
 * ARC2 RDF/XML Serializer.
 *
 * @author    Benjamin Nowack
 * @license   W3C Software License and GPL
 *
 * @homepage  <https://github.com/semsol/arc2>
 *
 * @version   2010-11-16
 */
ARC2::inc('RDFSerializer');

class ARC2_RDFXMLSerializer extends ARC2_RDFSerializer
{
    public string $content_header;
    public string $default_ns;
    public string $pp_containers;
    public int $type_nodes;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->content_header = 'application/rdf+xml';
        $this->pp_containers = $this->v('serializer_prettyprint_containers', 0, $this->a);
        $this->default_ns = $this->v('serializer_default_ns', '', $this->a);
        $this->type_nodes = $this->v('serializer_type_nodes', 0, $this->a);
    }

    public function getTerm($v, $type)
    {
        if (!is_array($v)) {/* uri or bnode */
            if (preg_match('/^\_\:(.*)$/', $v, $m)) {
                return ' rdf:nodeID="'.$m[1].'"';
            }
            if ('s' == $type) {
                return ' rdf:about="'.htmlspecialchars($v).'"';
            }
            if ('p' == $type) {
                $pn = $this->getPName($v);

                return $pn ? $pn : 0;
            }
            if ('o' == $type) {
                $v = $this->expandPName($v);
                if (!preg_match('/^[a-z0-9]{2,}\:[^\s]+$/is', $v)) {
                    return $this->getTerm(['value' => $v, 'type' => 'literal'], $type);
                }

                return ' rdf:resource="'.htmlspecialchars($v).'"';
            }
            if ('datatype' == $type) {
                $v = $this->expandPName($v);

                return ' rdf:datatype="'.htmlspecialchars($v).'"';
            }
            if ('lang' == $type) {
                return ' xml:lang="'.htmlspecialchars($v).'"';
            }
        }
        if ('literal' != $this->v('type', '', $v)) {
            return $this->getTerm($v['value'], 'o');
        }
        /* literal */
        $dt = isset($v['datatype']) ? $v['datatype'] : '';
        $lang = isset($v['lang']) ? $v['lang'] : '';
        if ('http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral' == $dt) {
            return ' rdf:parseType="Literal">'.$v['value'];
        } elseif ($dt) {
            return $this->getTerm($dt, 'datatype').'>'.htmlspecialchars($v['value']);
        } elseif ($lang) {
            return $this->getTerm($lang, 'lang').'>'.htmlspecialchars($v['value']);
        }

        return '>'.htmlspecialchars($this->v('value', '', $v));
    }

    public function getPName($v, $connector = ':')
    {
        if ($this->default_ns && str_starts_with($v, $this->default_ns)) {
            $pname = substr($v, strlen($this->default_ns));
            if (!preg_match('/\//', $pname)) {
                return $pname;
            }
        }

        return parent::getPName($v, $connector);
    }

    public function getHead()
    {
        $r = '';
        $nl = "\n";
        $r .= '<?xml version="1.0" encoding="UTF-8"?>';
        $r .= $nl.'<rdf:RDF';
        $first_ns = 1;
        foreach ($this->used_ns as $v) {
            $r .= $first_ns ? ' ' : $nl.'  ';
            foreach ($this->ns as $prefix => $ns) {
                if ($ns != $v) {
                    continue;
                }
                $r .= 'xmlns:'.$prefix.'="'.$v.'"';
                break;
            }
            $first_ns = 0;
        }
        if ($this->default_ns) {
            $r .= $first_ns ? ' ' : $nl.'  ';
            $r .= 'xmlns="'.$this->default_ns.'"';
        }
        $r .= '>';

        return $r;
    }

    public function getFooter()
    {
        $r = '';
        $nl = "\n";
        $r .= $nl.$nl.'</rdf:RDF>';

        return $r;
    }

    public function getSerializedIndex($index, $raw = 0)
    {
        $r = '';
        $nl = "\n";
        foreach ($index as $raw_s => $ps) {
            $r .= $r ? $nl.$nl : '';
            $s = $this->getTerm($raw_s, 's');
            $tag = 'rdf:Description';
            list($tag, $ps) = $this->getNodeTag($ps);
            $sub_ps = 0;
            /* pretty containers */
            if ($this->pp_containers && ($ctag = $this->getContainerTag($ps))) {
                $tag = 'rdf:'.$ctag;
                list($ps, $sub_ps) = $this->splitContainerEntries($ps);
            }
            $r .= '  <'.$tag.''.$s.'>';
            $first_p = 1;
            foreach ($ps as $p => $os) {
                if (!$os) {
                    continue;
                }
                $p = $this->getTerm($p, 'p');
                if ($p) {
                    $r .= $nl.str_pad('', 4);
                    $first_o = 1;
                    if (!is_array($os)) {/* single literal o */
                        $os = [['value' => $os, 'type' => 'literal']];
                    }
                    foreach ($os as $o) {
                        $o = $this->getTerm($o, 'o');
                        $r .= $first_o ? '' : $nl.'    ';
                        $r .= '<'.$p;
                        $r .= $o;
                        $r .= preg_match('/\>/', $o) ? '</'.$p.'>' : '/>';
                        $first_o = 0;
                    }
                    $first_p = 0;
                }
            }
            $r .= $r ? $nl.'  </'.$tag.'>' : '';
            if ($sub_ps) {
                $r .= $nl.$nl.$this->getSerializedIndex([$raw_s => $sub_ps], 1);
            }
        }
        if ($raw) {
            return $r;
        }

        return $this->getHead().$nl.$nl.$r.$this->getFooter();
    }

    public function getNodeTag($ps)
    {
        if (!$this->type_nodes) {
            return ['rdf:Description', $ps];
        }
        $rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $types = $this->v($rdf.'type', [], $ps);
        if (!$types) {
            return ['rdf:Description', $ps];
        }
        $type = array_shift($types);
        $ps[$rdf.'type'] = $types;
        if (!is_array($type)) {
            $type = ['value' => $type];
        }

        return [$this->getPName($type['value']), $ps];
    }

    public function getContainerTag($ps)
    {
        $rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        if (!isset($ps[$rdf.'type'])) {
            return '';
        }
        $types = $ps[$rdf.'type'];
        foreach ($types as $type) {
            if (!in_array($type['value'], [$rdf.'Bag', $rdf.'Seq', $rdf.'Alt'])) {
                return '';
            }

            return str_replace($rdf, '', $type['value']);
        }
    }

    public function splitContainerEntries($ps)
    {
        $rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $items = [];
        $rest = [];
        foreach ($ps as $p => $os) {
            $p_short = str_replace($rdf, '', $p);
            if ('type' === $p_short) {
                continue;
            }
            if (preg_match('/^\_([0-9]+)$/', $p_short, $m)) {
                $items = array_merge($items, $os);
            } else {
                $rest[$p] = $os;
            }
        }
        if ($items) {
            return [[$rdf.'li' => $items], $rest];
        }

        return [$rest, 0];
    }
}
