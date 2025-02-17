<?php
/**
 * ARC2 Turtle Serializer.
 *
 * @author    Benjamin Nowack
 * @license   W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 *
 * @version   2010-11-16
 */
ARC2::inc('RDFSerializer');

class ARC2_TurtleSerializer extends ARC2_RDFSerializer
{
    public string $content_header;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->content_header = 'application/x-turtle';
    }

    public function getTerm($v, $term = '', $qualifier = '')
    {
        if (!is_array($v)) {
            if (preg_match('/^\_\:/', $v)) {
                return $v;
            }
            if (('p' === $term) && ($pn = $this->getPName($v))) {
                return $pn;
            }
            if (
                ('o' === $term)
                && in_array($qualifier, ['rdf:type', 'rdfs:domain', 'rdfs:range', 'rdfs:subClassOf'])
                && ($pn = $this->getPName($v))
            ) {
                return $pn;
            }
            if (preg_match('/^[a-z0-9]+\:[^\s]*$/is'.($this->has_pcre_unicode ? 'u' : ''), $v)) {
                return '<'.$v.'>';
            }

            return $this->getTerm(['type' => 'literal', 'value' => $v], $term, $qualifier);
        }
        if (!isset($v['type']) || ('literal' != $v['type'])) {
            return $this->getTerm($v['value'], $term, $qualifier);
        }
        /* literal */
        $quot = '"';
        if (preg_match('/\"/', $v['value'])) {
            $quot = "'";
            if (preg_match('/\'/', $v['value']) || preg_match('/[\x0d\x0a]/', $v['value'])) {
                $quot = '"""';
                if (preg_match('/\"\"\"/', $v['value']) || preg_match('/\"$/', $v['value']) || preg_match('/^\"/', $v['value'])) {
                    $quot = "'''";
                    $v['value'] = preg_replace("/'$/", "' ", $v['value']);
                    $v['value'] = preg_replace("/^'/", " '", $v['value']);
                    $v['value'] = str_replace("'''", '\\\'\\\'\\\'', $v['value']);
                }
            }
        }
        if ((1 == strlen($quot)) && preg_match('/[\x0d\x0a]/', $v['value'])) {
            $quot = $quot.$quot.$quot;
        }
        $suffix = isset($v['lang']) && $v['lang'] ? '@'.$v['lang'] : '';
        $suffix = isset($v['datatype']) && $v['datatype'] ? '^^'.$this->getTerm($v['datatype'], 'dt') : $suffix;

        return $quot.$v['value'].$quot.$suffix;
    }

    public function getHead()
    {
        $r = '';
        $nl = "\n";
        foreach ($this->used_ns as $v) {
            $r .= $r ? $nl : '';
            foreach ($this->ns as $prefix => $ns) {
                if ($ns != $v) {
                    continue;
                }
                $r .= '@prefix '.$prefix.': <'.$v.'> .';
                break;
            }
        }

        return $r;
    }

    public function getSerializedIndex($index, $raw = 0)
    {
        $r = '';
        $nl = "\n";
        foreach ($index as $s => $ps) {
            $r .= $r ? ' .'.$nl.$nl : '';
            $s = $this->getTerm($s, 's');
            $r .= $s;
            $first_p = 1;
            foreach ($ps as $p => $os) {
                if (!$os) {
                    continue;
                }
                $p = $this->getTerm($p, 'p');
                $r .= $first_p ? ' ' : ' ;'.$nl.str_pad('', strlen($s) + 1);
                $r .= $p;
                $first_o = 1;
                if (!is_array($os)) {/* single literal o */
                    $os = [['value' => $os, 'type' => 'literal']];
                }
                foreach ($os as $o) {
                    $r .= $first_o ? ' ' : ' ,'.$nl.str_pad('', strlen($s) + strlen($p) + 2);
                    $o = $this->getTerm($o, 'o', $p);
                    $r .= $o;
                    $first_o = 0;
                }
                $first_p = 0;
            }
        }
        $r .= $r ? ' .' : '';
        if ($raw) {
            return $r;
        }

        return $r ? $this->getHead().$nl.$nl.$r : '';
    }
}
