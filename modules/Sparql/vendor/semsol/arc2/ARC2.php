<?php
/**
 * ARC2 core class (static, not instantiated).
 *
 * @author Benjamin Nowack
 *
 * @homepage <https://github.com/semsol/arc2>
 */

/* E_STRICT hack */
if (function_exists('date_default_timezone_get')) {
    date_default_timezone_set(date_default_timezone_get());
}

/**
 * @deprecated dont rely on this class, because it gets removed in the future
 */
class ARC2
{
    public static function getVersion()
    {
        return '2011-12-01';
    }

    public static function getIncPath($f = '')
    {
        $r = realpath(__DIR__).'/';
        $dirs = [
            'plugin' => 'plugins',
            'trigger' => 'triggers',
            'store' => 'store',
            'serializer' => 'serializers',
            'extractor' => 'extractors',
            'sparqlscript' => 'sparqlscript',
            'parser' => 'parsers',
        ];
        foreach ($dirs as $k => $dir) {
            if (preg_match('/'.$k.'/i', $f)) {
                return $r.$dir.'/';
            }
        }

        return $r;
    }

    public static function getScriptURI()
    {
        if (isset($_SERVER) && (isset($_SERVER['SERVER_NAME']) || isset($_SERVER['HTTP_HOST']))) {
            $proto = preg_replace('/^([a-z]+)\/.*$/', '\\1', strtolower($_SERVER['SERVER_PROTOCOL']));
            $port = $_SERVER['SERVER_PORT'];
            $server = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
            $script = $_SERVER['SCRIPT_NAME'];
            /* https */
            if (('http' == $proto) && 443 == $port) {
                $proto = 'https';
                $port = 80;
            }

            return $proto.'://'.$server.(80 != $port ? ':'.$port : '').$script;
        } elseif (isset($_SERVER['SCRIPT_FILENAME'])) {
            return 'file://'.realpath($_SERVER['SCRIPT_FILENAME']);
        }

        return 'http://localhost/unknown_path';
    }

    public static function getRequestURI()
    {
        if (isset($_SERVER) && isset($_SERVER['REQUEST_URI'])) {
            return preg_replace('/^([a-z]+)\/.*$/', '\\1', strtolower($_SERVER['SERVER_PROTOCOL'])).
        '://'.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']).
        (80 != $_SERVER['SERVER_PORT'] ? ':'.$_SERVER['SERVER_PORT'] : '').
        $_SERVER['REQUEST_URI'];
        }

        return self::getScriptURI();
    }

    public static function inc($f, $path = '')
    {
        $prefix = 'ARC2';
        if (preg_match('/^([^\_]+)\_(.*)$/', $f, $m)) {
            $prefix = $m[1];
            $f = $m[2];
        }
        $inc_path = $path ?: self::getIncPath($f);
        $path = $inc_path.$prefix.'_'.urlencode($f).'.php';
        if (file_exists($path)) {
            return include_once $path;
        } elseif ('ARC2' != $prefix) {
            /* try other path */
            $path = $inc_path.strtolower($prefix).'/'.$prefix.'_'.urlencode($f).'.php';
            if (file_exists($path)) {
                return include_once $path;
            } else {
                return 0;
            }
        }

        return 0;
    }

    public static function mtime()
    {
        return microtime(true);
    }

    public static function x($re, $v, $options = 'si')
    {
        return preg_match("/^\s*".$re.'(.*)$/'.$options, $v, $m) ? $m : false;
    }

    public static function getFormat($val, $mtype = '', $ext = '')
    {
        self::inc('getFormat');

        return ARC2_getFormat($val, $mtype, $ext);
    }

    public static function getPreferredFormat($default = 'plain')
    {
        self::inc('getPreferredFormat');

        return ARC2_getPreferredFormat($default);
    }

    public static function toUTF8($v)
    {
        if (urlencode($v) === $v) {
            return $v;
        }
        $str = mb_convert_encoding(str_replace('?', '', $v), 'ISO-8859-1', 'UTF-8');
        $v = false === str_contains($str, '?') ? mb_convert_encoding($v, 'ISO-8859-1', 'UTF-8') : $v;

        /* custom hacks, mainly caused by bugs in PHP's json_decode */
        $mappings = [
            '%18' => '‘',
            '%19' => '’',
            '%1C' => '“',
            '%1D' => '”',
            '%1E' => '„',
            '%10' => '‐',
            '%12' => '−',
            '%13' => '–',
            '%14' => '—',
            '%26' => '&',
        ];
        $froms = array_keys($mappings);
        $tos = array_values($mappings);
        foreach ($froms as $i => $from) {
            $froms[$i] = urldecode($from);
        }
        $v = str_replace($froms, $tos, $v);

        /* utf8 tweaks */
        return preg_replace_callback('/([\x00-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xf7][\x80-\xbf]{3}|[\xf8-\xfb][\x80-\xbf]{4}|[\xfc-\xfd][\x80-\xbf]{5}|[^\x00-\x7f])/', ['ARC2', 'getUTF8Char'], $v);
    }

    public static function getUTF8Char($v)
    {
        $val = $v[1];
        if (1 === strlen(trim($val))) {
            return mb_convert_encoding($val, 'UTF-8', mb_list_encodings());
        }
        if (preg_match('/^([\x00-\x7f])(.+)/', $val, $m)) {
            return $m[1].self::toUTF8($m[2]);
        }

        return $val;
    }

    public static function splitURI($v)
    {
        /* the following namespaces may lead to conflated URIs,
         * we have to set the split position manually
        */
        if (strpos($v, 'www.w3.org')) {
            $specials = [
                'http://www.w3.org/XML/1998/namespace',
                'http://www.w3.org/2005/Atom',
                'http://www.w3.org/1999/xhtml',
            ];
            foreach ($specials as $ns) {
                if (str_starts_with($v, $ns)) {
                    $local_part = substr($v, strlen($ns));
                    if (!preg_match('/^[\/\#]/', $local_part)) {
                        return [$ns, $local_part];
                    }
                }
            }
        }
        /* auto-splitting on / or # */
        // $re = '^(.*?)([A-Z_a-z][-A-Z_a-z0-9.]*)$';
        if (preg_match('/^(.*[\/\#])([^\/\#]+)$/', $v, $m)) {
            return [$m[1], $m[2]];
        }
        /* auto-splitting on last special char, e.g. urn:foo:bar */
        if (preg_match('/^(.*[\:\/])([^\:\/]+)$/', $v, $m)) {
            return [$m[1], $m[2]];
        }

        return [$v, ''];
    }

    public static function getSimpleIndex($triples, $flatten_objects = 1, $vals = '')
    {
        $r = [];
        foreach ($triples as $t) {
            $skip_t = 0;
            foreach (['s', 'p', 'o'] as $term) {
                $$term = $t[$term];
                /* template var */
                if (isset($t[$term.'_type']) && ('var' == $t[$term.'_type'])) {
                    $val = isset($vals[$$term]) ? $vals[$$term] : '';
                    $skip_t = isset($vals[$$term]) ? $skip_t : 1;
                    $type = '';
                    $type = !$type && isset($vals[$$term.' type']) ? $vals[$$term.' type'] : $type;
                    $type = !$type && preg_match('/^\_\:/', $val) ? 'bnode' : $type;
                    if ('o' == $term) {
                        $type = !$type && (preg_match('/\s/s', $val) || !preg_match('/\:/', $val)) ? 'literal' : $type;
                        $type = !$type && !preg_match('/[\/]/', $val) ? 'literal' : $type;
                    }
                    $type = !$type ? 'uri' : $type;
                    $t[$term.'_type'] = $type;
                    $$term = $val;
                }
            }
            if ($skip_t) {
                continue;
            }
            if (!isset($r[$s])) {
                $r[$s] = [];
            }
            if (!isset($r[$s][$p])) {
                $r[$s][$p] = [];
            }
            if ($flatten_objects) {
                if (!in_array($o, $r[$s][$p])) {
                    $r[$s][$p][] = $o;
                }
            } else {
                $o = ['value' => $o];
                foreach (['lang', 'type', 'datatype'] as $suffix) {
                    if (isset($t['o_'.$suffix]) && $t['o_'.$suffix]) {
                        $o[$suffix] = $t['o_'.$suffix];
                    } elseif (isset($t['o '.$suffix]) && $t['o '.$suffix]) {
                        $o[$suffix] = $t['o '.$suffix];
                    }
                }
                if (!in_array($o, $r[$s][$p])) {
                    $r[$s][$p][] = $o;
                }
            }
        }

        return $r;
    }

    public static function getTriplesFromIndex($index)
    {
        $r = [];
        foreach ($index as $s => $ps) {
            foreach ($ps as $p => $os) {
                foreach ($os as $o) {
                    $r[] = [
                        's' => $s,
                        'p' => $p,
                        'o' => $o['value'],
                        's_type' => preg_match('/^\_\:/', $s) ? 'bnode' : 'uri',
                        'o_type' => $o['type'],
                        'o_datatype' => isset($o['datatype']) ? $o['datatype'] : '',
                        'o_lang' => isset($o['lang']) ? $o['lang'] : '',
                    ];
                }
            }
        }

        return $r;
    }

    public static function getMergedIndex()
    {
        $r = [];
        foreach (func_get_args() as $index) {
            foreach ($index as $s => $ps) {
                if (!isset($r[$s])) {
                    $r[$s] = [];
                }
                foreach ($ps as $p => $os) {
                    if (!isset($r[$s][$p])) {
                        $r[$s][$p] = [];
                    }
                    foreach ($os as $o) {
                        if (!in_array($o, $r[$s][$p])) {
                            $r[$s][$p][] = $o;
                        }
                    }
                }
            }
        }

        return $r;
    }

    public static function getCleanedIndex()
    {/* removes triples from a given index */
        $indexes = func_get_args();
        $r = $indexes[0];
        for ($i = 1, $i_max = count($indexes); $i < $i_max; ++$i) {
            $index = $indexes[$i];
            foreach ($index as $s => $ps) {
                if (!isset($r[$s])) {
                    continue;
                }
                foreach ($ps as $p => $os) {
                    if (!isset($r[$s][$p])) {
                        continue;
                    }
                    $r_os = $r[$s][$p];
                    $new_os = [];
                    foreach ($r_os as $r_o) {
                        $r_o_val = is_array($r_o) ? $r_o['value'] : $r_o;
                        $keep = 1;
                        foreach ($os as $o) {
                            $del_o_val = is_array($o) ? $o['value'] : $o;
                            if ($del_o_val == $r_o_val) {
                                $keep = 0;
                                break;
                            }
                        }
                        if ($keep) {
                            $new_os[] = $r_o;
                        }
                    }
                    if ($new_os) {
                        $r[$s][$p] = $new_os;
                    } else {
                        unset($r[$s][$p]);
                    }
                }
            }
        }
        /* check r */
        $has_data = 0;
        foreach ($r as $s => $ps) {
            if ($ps) {
                $has_data = 1;
                break;
            }
        }

        return $has_data ? $r : [];
    }

    public static function getStructType($v)
    {
        /* string */
        if (is_string($v)) {
            return 'string';
        }
        /* flat array, numeric keys */
        if (in_array(0, array_keys($v))) {/* numeric keys */
            /* simple array */
            if (!is_array($v[0])) {
                return 'array';
            }
            /* triples */
            // if (isset($v[0]) && isset($v[0]['s']) && isset($v[0]['p'])) return 'triples';
            if (in_array('p', array_keys($v[0]))) {
                return 'triples';
            }
        }
        /* associative array */
        else {
            /* index */
            foreach ($v as $s => $ps) {
                if (!is_array($ps)) {
                    break;
                }
                foreach ($ps as $p => $os) {
                    if (!is_array($os) || !is_array($os[0])) {
                        break;
                    }
                    if (in_array('value', array_keys($os[0]))) {
                        return 'index';
                    }
                }
            }
        }

        /* array */
        return 'array';
    }

    public static function getComponent($name, $a = '', $caller = '')
    {
        self::inc($name);
        $prefix = 'ARC2';
        if (preg_match('/^([^\_]+)\_(.+)$/', $name, $m)) {
            $prefix = $m[1];
            $name = $m[2];
        }
        $cls = $prefix.'_'.$name;
        if (!$caller) {
            $caller = new stdClass();
        }

        return new $cls($a, $caller);
    }

    /* graph */

    public static function getGraph($a = '')
    {
        return self::getComponent('Graph', $a);
    }

    /* resource */

    public static function getResource($a = '')
    {
        return self::getComponent('Resource', $a);
    }

    /* reader */

    public static function getReader($a = '')
    {
        return self::getComponent('Reader', $a);
    }

    /* parsers */

    public static function getParser($prefix, $a = '')
    {
        return self::getComponent($prefix.'Parser', $a);
    }

    public static function getRDFParser($a = '')
    {
        return self::getParser('RDF', $a);
    }

    public static function getRDFXMLParser($a = '')
    {
        return self::getParser('RDFXML', $a);
    }

    public static function getTurtleParser($a = '')
    {
        return self::getParser('Turtle', $a);
    }

    public static function getRSSParser($a = '')
    {
        return self::getParser('RSS', $a);
    }

    public static function getSemHTMLParser($a = '')
    {
        return self::getParser('SemHTML', $a);
    }

    public static function getSPARQLParser($a = '')
    {
        return self::getComponent('SPARQLParser', $a);
    }

    public static function getSPARQLPlusParser($a = '')
    {
        return self::getParser('SPARQLPlus', $a);
    }

    public static function getSPARQLXMLResultParser($a = '')
    {
        return self::getParser('SPARQLXMLResult', $a);
    }

    public static function getJSONParser($a = '')
    {
        return self::getParser('JSON', $a);
    }

    public static function getSGAJSONParser($a = '')
    {
        return self::getParser('SGAJSON', $a);
    }

    public static function getCBJSONParser($a = '')
    {
        return self::getParser('CBJSON', $a);
    }

    public static function getSPARQLScriptParser($a = '')
    {
        return self::getParser('SPARQLScript', $a);
    }

    /* store */

    public static function getStore($a = '', $caller = '')
    {
        return self::getComponent('Store', $a, $caller);
    }

    public static function getStoreEndpoint($a = '', $caller = '')
    {
        return self::getComponent('StoreEndpoint', $a, $caller);
    }

    public static function getRemoteStore($a = '', $caller = '')
    {
        return self::getComponent('RemoteStore', $a, $caller);
    }

    public static function getMemStore($a = '')
    {
        return self::getComponent('MemStore', $a);
    }

    /* serializers */

    public static function getSer($prefix, $a = '')
    {
        return self::getComponent($prefix.'Serializer', $a);
    }

    public static function getTurtleSerializer($a = '')
    {
        return self::getSer('Turtle', $a);
    }

    public static function getRDFXMLSerializer($a = '')
    {
        return self::getSer('RDFXML', $a);
    }

    public static function getNTriplesSerializer($a = '')
    {
        return self::getSer('NTriples', $a);
    }

    public static function getRDFJSONSerializer($a = '')
    {
        return self::getSer('RDFJSON', $a);
    }

    public static function getPOSHRDFSerializer($a = '')
    {/* deprecated */
        return self::getSer('POSHRDF', $a);
    }

    public static function getMicroRDFSerializer($a = '')
    {
        return self::getSer('MicroRDF', $a);
    }

    public static function getRSS10Serializer($a = '')
    {
        return self::getSer('RSS10', $a);
    }

    public static function getJSONLDSerializer($a = '')
    {
        return self::getSer('JSONLD', $a);
    }

    /* sparqlscript */

    public static function getSPARQLScriptProcessor($a = '')
    {
        return self::getComponent('SPARQLScriptProcessor', $a);
    }
}
