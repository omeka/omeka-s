<?php
/**
 * ARC2 JSON Parser
 * Does not extract triples, needs sub-class for RDF extraction.
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 *
 * @version 2010-11-16
 */
ARC2::inc('RDFParser');

class ARC2_JSONParser extends ARC2_RDFParser
{
    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
    }

    public function x($re, $v, $options = 'si')
    {
        while (preg_match('/^\s*(\/\*.*\*\/)(.*)$/Usi', $v, $m)) {/* comment removal */
            $v = $m[2];
        }
        $this->unparsed_code = (strlen($this->unparsed_code) > strlen($v)) ? $v : $this->unparsed_code;

        return ARC2::x($re, $v, $options);
    }

    public function parse($path, $data = '')
    {
        $this->state = 0;
        /* reader */
        if (!$this->v('reader')) {
            ARC2::inc('Reader');
            $this->reader = new ARC2_Reader($this->a, $this);
        }
        $this->reader->setAcceptHeader('Accept: application/json; q=0.9, */*; q=0.1');
        $this->reader->activate($path, $data);
        $this->x_base = isset($this->a['base']) && $this->a['base'] ? $this->a['base'] : $this->reader->base;
        /* parse */
        $doc = '';
        while ($d = $this->reader->readStream()) {
            $doc .= $d;
        }
        $this->reader->closeStream();
        unset($this->reader);
        $doc = preg_replace('/^[^\{]*(.*\})[^\}]*$/is', '\\1', $doc);
        $this->unparsed_code = $doc;
        list($this->struct, $rest) = $this->extractObject($doc);

        return $this->done();
    }

    public function extractObject($v)
    {
        if (function_exists('json_decode')) {
            return [json_decode($v, 1), ''];
        }
        $r = [];
        /* sub-object */
        if ($sub_r = $this->x('\{', $v)) {
            $v = $sub_r[1];
            while ((list($sub_r, $v) = $this->extractEntry($v)) && $sub_r) {
                $r[$sub_r['key']] = $sub_r['value'];
            }
            if ($sub_r = $this->x('\}', $v)) {
                $v = $sub_r[1];
            }
        }
        /* sub-list */
        elseif ($sub_r = $this->x('\[', $v)) {
            $v = $sub_r[1];
            while ((list($sub_r, $v) = $this->extractObject($v)) && $sub_r) {
                $r[] = $sub_r;
                $v = ltrim($v, ',');
            }
            if ($sub_r = $this->x('\]', $v)) {
                $v = $sub_r[1];
            }
        }
        /* sub-value */
        elseif ((list($sub_r, $v) = $this->extractValue($v)) && (false !== $sub_r)) {
            $r = $sub_r;
        }

        return [$r, $v];
    }

    public function extractEntry($v)
    {
        if ($r = $this->x('\,', $v)) {
            $v = $r[1];
        }
        /* k */
        if ($r = $this->x('\"([^\"]+)\"\s*\:', $v)) {
            $k = $r[1];
            $sub_v = $r[2];
            if (list($sub_r, $sub_v) = $this->extractObject($sub_v)) {
                return [
                    ['key' => $k, 'value' => $sub_r],
                    $sub_v,
                ];
            }
        }

        return [0, $v];
    }

    public function extractValue($v)
    {
        if ($r = $this->x('\,', $v)) {
            $v = $r[1];
        }
        if ($sub_r = $this->x('null', $v)) {
            return [null, $sub_r[1]];
        }
        if ($sub_r = $this->x('(true|false)', $v)) {
            return [$sub_r[1], $sub_r[2]];
        }
        if ($sub_r = $this->x('([\-\+]?[0-9\.]+)', $v)) {
            return [$sub_r[1], $sub_r[2]];
        }
        if ($sub_r = $this->x('\"', $v)) {
            $rest = $sub_r[1];
            if (preg_match('/^([^\x5c]*|.*[^\x5c]|.*\x5c{2})\"(.*)$/sU', $rest, $m)) {
                $val = $m[1];
                /* unescape chars (single-byte) */
                $val = preg_replace_callback('/\\\u(.{4})/', function ($matches) {
                    return chr(hexdec($matches[1]));
                }, $val);
                // $val = preg_replace_callback('/\\\u00(.{2})', function($matches) { return rawurldecode("%" . $matches[1]); }, $val);
                /* other escaped chars */
                $from = ['\\\\', '\r', '\t', '\n', '\"', '\b', '\f', '\/'];
                $to = ['\\', "\r", "\t", "\n", '"', "\b", "\f", '/'];
                $val = str_replace($from, $to, $val);

                return [$val, $m[2]];
            }
        }

        return [false, $v];
    }

    public function getObject()
    {
        return $this->v('struct', []);
    }

    public function getTriples()
    {
        return $this->v('triples', []);
    }

    public function countTriples()
    {
        return $this->t_count;
    }

    public function addT($s = '', $p = '', $o = '', $s_type = '', $o_type = '', $o_dt = '', $o_lang = '')
    {
        $o = $this->toUTF8($o);
        // echo str_replace($this->base, '', "-----\n adding $s / $p / $o\n-----\n");
        $t = ['s' => $s, 'p' => $p, 'o' => $o, 's_type' => $s_type, 'o_type' => $o_type, 'o_datatype' => $o_dt, 'o_lang' => $o_lang];
        if ($this->skip_dupes) {
            $h = md5(serialize($t));
            if (!isset($this->added_triples[$h])) {
                $this->triples[$this->t_count] = $t;
                ++$this->t_count;
                $this->added_triples[$h] = true;
            }
        } else {
            $this->triples[$this->t_count] = $t;
            ++$this->t_count;
        }
    }
}
