<?php
/**
 * ARC2 streaming SPOG parser.
 *
 * @author Benjamin Nowack
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 *
 * @version 2010-11-16
 */
ARC2::inc('RDFParser');

class ARC2_SPOGParser extends ARC2_RDFParser
{
    public string $binding;
    public $encoding;
    public int $prev_state;
    public string $rdf;
    public int $state;

    /**
     * @var array<mixed>
     */
    public array $t;
    public string $target_encoding;
    public string $tmp_error;
    public $x_base;
    public string $xml;
    public $xml_parser;
    public string $xsd;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {/* reader */
        parent::__init();
        $this->encoding = $this->v('encoding', false, $this->a);
        $this->xml = 'http://www.w3.org/XML/1998/namespace';
        $this->rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $this->nsp = [$this->xml => 'xml', $this->rdf => 'rdf'];
        $this->target_encoding = '';
    }

    public function parse($path, $data = '', $iso_fallback = false)
    {
        $this->state = 0;
        /* reader */
        if (!$this->v('reader')) {
            ARC2::inc('Reader');
            $this->reader = new ARC2_Reader($this->a, $this);
        }
        $this->reader->setAcceptHeader('Accept: sparql-results+xml; q=0.9, */*; q=0.1');
        $this->reader->activate($path, $data);
        $this->x_base = isset($this->a['base']) && $this->a['base'] ? $this->a['base'] : $this->reader->base;
        /* xml parser */
        $this->initXMLParser();
        /* parse */
        $first = true;
        while ($d = $this->reader->readStream()) {
            if ($iso_fallback && $first) {
                $d = '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n".preg_replace('/^\<\?xml [^\>]+\?\>\s*/s', '', $d);
                $first = false;
            }
            if (!xml_parse($this->xml_parser, $d, false)) {
                $error_str = xml_error_string(xml_get_error_code($this->xml_parser));
                $line = xml_get_current_line_number($this->xml_parser);
                $this->tmp_error = 'XML error: "'.$error_str.'" at line '.$line.' (parsing as '.$this->getEncoding().')';
                $this->tmp_error .= $d.urlencode($d);
                if (0 && !$iso_fallback && preg_match('/Invalid character/i', $error_str)) {
                    xml_parser_free($this->xml_parser);
                    unset($this->xml_parser);
                    $this->reader->closeStream();
                    $this->__init();
                    $this->encoding = 'ISO-8859-1';
                    unset($this->xml_parser);
                    unset($this->reader);

                    return $this->parse($path, $data, true);
                } else {
                    return $this->addError($this->tmp_error);
                }
            }
        }
        $this->target_encoding = xml_parser_get_option($this->xml_parser, \XML_OPTION_TARGET_ENCODING);
        xml_parser_free($this->xml_parser);
        $this->reader->closeStream();
        unset($this->reader);

        return $this->done();
    }

    public function initXMLParser()
    {
        if (!isset($this->xml_parser)) {
            $enc = preg_match('/^(utf\-8|iso\-8859\-1|us\-ascii)$/i', $this->getEncoding(), $m) ? $m[1] : 'UTF-8';
            $parser = xml_parser_create($enc);
            xml_parser_set_option($parser, \XML_OPTION_SKIP_WHITE, 0);
            xml_parser_set_option($parser, \XML_OPTION_CASE_FOLDING, 0);
            xml_set_element_handler($parser, [$this, 'open'], [$this, 'close']);
            xml_set_character_data_handler($parser, [$this, 'cdata']);
            xml_set_start_namespace_decl_handler($parser, [$this, 'nsDecl']);
            $this->xml_parser = $parser;
        }
    }

    public function getEncoding($src = 'config')
    {
        if ('parser' == $src) {
            return $this->target_encoding;
        } elseif (('config' == $src) && $this->encoding) {
            return $this->encoding;
        }

        return $this->reader->getEncoding();

        return 'UTF-8';
    }

    public function getTriples()
    {
        return $this->v('triples', []);
    }

    public function countTriples()
    {
        return $this->t_count;
    }

    public function addT($s = '', $p = '', $o = '', $s_type = '', $o_type = '', $o_dt = '', $o_lang = '', $g = '')
    {
        if (!($s && $p && $o)) {
            return 0;
        }
        // echo "-----\nadding $s / $p / $o\n-----\n";
        $t = ['s' => $s, 'p' => $p, 'o' => $o, 's_type' => $s_type, 'o_type' => $o_type, 'o_datatype' => $o_dt, 'o_lang' => $o_lang, 'g' => $g];
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

    public function open($p, $t, $a)
    {
        $this->state = $t;
        if ('result' == $t) {
            $this->t = [];
        } elseif ('binding' == $t) {
            $this->binding = $a['name'];
            $this->t[$this->binding] = '';
        } elseif ('literal' == $t) {
            $this->t[$this->binding.'_dt'] = $this->v('datatype', '', $a);
            $this->t[$this->binding.'_lang'] = $this->v('xml:lang', '', $a);
            $this->t[$this->binding.'_type'] = 'literal';
        } elseif ('uri' == $t) {
            $this->t[$this->binding.'_type'] = 'uri';
        } elseif ('bnode' == $t) {
            $this->t[$this->binding.'_type'] = 'bnode';
            $this->t[$this->binding] = '_:';
        }
    }

    public function close($p, $t)
    {
        $this->prev_state = $this->state;
        $this->state = '';
        if ('result' == $t) {
            $this->addT(
                $this->v('s', '', $this->t),
                $this->v('p', '', $this->t),
                $this->v('o', '', $this->t),
                $this->v('s_type', '', $this->t),
                $this->v('o_type', '', $this->t),
                $this->v('o_dt', '', $this->t),
                $this->v('o_lang', '', $this->t),
                $this->v('g', '', $this->t)
            );
        }
    }

    public function cData($p, $d)
    {
        if (in_array($this->state, ['uri', 'bnode', 'literal'])) {
            $this->t[$this->binding] .= $d;
        }
    }

    public function nsDecl($p, $prf, $uri)
    {
        $this->nsp[$uri] = isset($this->nsp[$uri]) ? $this->nsp[$uri] : $prf;
    }
}
