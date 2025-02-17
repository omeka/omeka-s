<?php
/**
 * ARC2 base class.
 *
 * @author Benjamin Nowack
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 */
class ARC2_Class
{
    /**
     * @var array<mixed>
     */
    public array $a = [];

    public int $adjust_utf8;

    public string $base;

    public $caller;

    public $db_object;

    /**
     * @var array<mixed>
     */
    public array $errors = [];

    public $has_pcre_unicode;

    public string $inc_path;

    public int $max_errors;

    /**
     * @var array<mixed>
     */
    public array $ns;

    public int $ns_count;

    /**
     * @var array<mixed>
     */
    public array $nsp;

    /**
     * @var array<string>
     */
    public array $used_ns = [];

    /**
     * @var array<string>
     */
    public array $warnings = [];

    public function __construct($a, &$caller)
    {
        $this->a = is_array($a) ? $a : [];
        $this->caller = $caller;
        $this->__init();
    }

    public function __init()
    {/* base, time_limit */
        if (!$_POST && isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            parse_str($GLOBALS['HTTP_RAW_POST_DATA'], $_POST);
        } /* php5 bug */
        $this->inc_path = ARC2::getIncPath();
        $this->ns_count = 0;
        $rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $this->nsp = [$rdf => 'rdf'];
        $this->used_ns = [$rdf];
        $this->ns = array_merge(['rdf' => $rdf], $this->v('ns', [], $this->a));

        $this->base = $this->v('base', ARC2::getRequestURI(), $this->a);
        $this->errors = [];
        $this->warnings = [];
        $this->adjust_utf8 = $this->v('adjust_utf8', 0, $this->a);
        $this->max_errors = $this->v('max_errors', 25, $this->a);
        $this->has_pcre_unicode = preg_match('/\pL/u', 'test'); /* \pL = block/point which is a Letter */
    }

    public function v($name, $default = false, $o = false)
    {/* value if set */
        if (false === $o) {
            $o = $this;
        }
        if (is_array($o)) {
            return isset($o[$name]) ? $o[$name] : $default;
        }

        return isset($o->$name) ? $o->$name : $default;
    }

    public function v1($name, $default = false, $o = false)
    {/* value if 1 (= not empty) */
        if (false === $o) {
            $o = $this;
        }
        if (is_array($o)) {
            return (isset($o[$name]) && $o[$name]) ? $o[$name] : $default;
        }

        return (isset($o->$name) && $o->$name) ? $o->$name : $default;
    }

    public function m($name, $a = false, $default = false, $o = false)
    {/* call method */
        if (false === $o) {
            $o = $this;
        }

        return method_exists($o, $name) ? $o->$name($a) : $default;
    }

    public function camelCase($v, $lc_first = 0, $keep_boundaries = 0)
    {
        $r = ucfirst($v);
        while (preg_match('/^(.*)[^a-z0-9](.*)$/si', $r, $m)) {
            /* don't fuse 2 upper-case chars */
            if ($keep_boundaries && $m[1]) {
                $boundary = substr($m[1], -1);
                if (strtoupper($boundary) == $boundary) {
                    $m[1] .= 'CAMELCASEBOUNDARY';
                }
            }
            $r = $m[1].ucfirst($m[2]);
        }
        $r = str_replace('CAMELCASEBOUNDARY', '_', $r);
        if ((strlen($r) > 1) && $lc_first && !preg_match('/[A-Z]/', $r[1])) {
            $r = strtolower($r[0]).substr($r, 1);
        }

        return $r;
    }

    public function deCamelCase($v, $uc_first = 0)
    {
        $r = str_replace('_', ' ', $v);
        $r = preg_replace_callback('/([a-z0-9])([A-Z])/', function ($matches) {
            return $matches[1].' '.strtolower($matches[2]);
        }, $r);

        return $uc_first ? ucfirst($r) : $r;
    }

    /**
     * Tries to extract a somewhat human-readable label from a URI.
     */
    public function extractTermLabel($uri, $loops = 0)
    {
        list($ns, $r) = $this->splitURI($uri);
        /* encode apostrophe + s */
        $r = str_replace('%27s', '_apostrophes_', $r);
        /* normalize */
        $r = $this->deCamelCase($this->camelCase($r, 1, 1));
        /* decode apostrophe + s */
        $r = str_replace(' apostrophes ', "'s ", $r);
        /* typical RDF non-info URI */
        if (($loops < 1) && preg_match('/^(self|it|this|me|id)$/i', $r)) {
            return $this->extractTermLabel(preg_replace('/\#.+$/', '', $uri), $loops + 1);
        }
        /* trailing hash or slash */
        if ($uri && !$r && ($loops < 2)) {
            return $this->extractTermLabel(preg_replace('/[\#\/]$/', '', $uri), $loops + 1);
        }
        /* a de-camel-cased URL (will look like "www example com") */
        if (preg_match('/^www (.+ [a-z]{2,4})$/', $r, $m)) {
            return $this->getPrettyURL($uri);
        }

        return $r;
    }

    /**
     * Generates a less ugly in-your-face URL.
     */
    public function getPrettyURL($r)
    {
        $r = rtrim($r, '/');
        $r = preg_replace('/^https?\:\/\/(www\.)?/', '', $r);

        return $r;
    }

    public function addError($v)
    {
        if (!in_array($v, $this->errors)) {
            $this->errors[] = $v;
        }
        if ($this->caller && method_exists($this->caller, 'addError')) {
            $glue = str_contains((string) $v, ' in ') ? ' via ' : ' in ';
            $this->caller->addError($v.$glue.static::class);
        }
        if (count($this->errors) > $this->max_errors) {
            exit('Too many errors (limit: '.$this->max_errors.'): '.print_r($this->errors, 1));
        }

        return false;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    public function resetErrors()
    {
        $this->errors = [];
        if ($this->caller && method_exists($this->caller, 'resetErrors')) {
            $this->caller->resetErrors();
        }
    }

    public function splitURI($v)
    {
        return ARC2::splitURI($v);
    }

    public function getPName($v, $connector = ':')
    {
        /* is already a pname */
        $ns = $this->getPNameNamespace($v, $connector);
        if ($ns) {
            if (!in_array($ns, $this->used_ns)) {
                $this->used_ns[] = $ns;
            }

            return $v;
        }
        /* new pname */
        $parts = $this->splitURI($v);
        if ($parts) {
            /* known prefix */
            foreach ($this->ns as $prefix => $ns) {
                if ($parts[0] == $ns) {
                    if (!in_array($ns, $this->used_ns)) {
                        $this->used_ns[] = $ns;
                    }

                    return $prefix.$connector.$parts[1];
                }
            }
            /* new prefix */
            $prefix = $this->getPrefix($parts[0]);

            return $prefix.$connector.$parts[1];
        }

        return $v;
    }

    public function getPNameNamespace($v, $connector = ':')
    {
        $re = '/^([a-z0-9\_\-]+)\:([a-z0-9\_\-\.\%]+)$/i';
        if (':' != $connector) {
            $connectors = ['\:', '\-', '\_', '\.'];
            $chars = implode('', array_diff($connectors, [$connector]));
            $re = '/^([a-z0-9'.$chars.']+)\\'.$connector.'([a-z0-9\_\-\.\%]+)$/i';
        }
        if (!preg_match($re, $v, $m)) {
            return 0;
        }
        if (!isset($this->ns[$m[1]])) {
            return 0;
        }

        return $this->ns[$m[1]];
    }

    public function setPrefix($prefix, $ns)
    {
        $this->ns[$prefix] = $ns;
        $this->nsp[$ns] = $prefix;

        return $this;
    }

    public function getPrefix($ns)
    {
        if (!isset($this->nsp[$ns])) {
            $this->ns['ns'.$this->ns_count] = $ns;
            $this->nsp[$ns] = 'ns'.$this->ns_count;
            ++$this->ns_count;
        }
        if (!in_array($ns, $this->used_ns)) {
            $this->used_ns[] = $ns;
        }

        return $this->nsp[$ns];
    }

    public function expandPName($v, $connector = ':')
    {
        $re = '/^([a-z0-9\_\-]+)\:([a-z0-9\_\-\.\%]+)$/i';
        if (':' != $connector) {
            $connectors = [':', '-', '_', '.'];
            $chars = '\\'.implode('\\', array_diff($connectors, [$connector]));
            $re = '/^([a-z0-9'.$chars.']+)\\'.$connector.'([a-z0-9\_\-\.\%]+)$/Ui';
        }
        if (preg_match($re, $v, $m) && isset($this->ns[$m[1]])) {
            return $this->ns[$m[1]].$m[2];
        }

        return $v;
    }

    public function expandPNames($index)
    {
        $r = [];
        foreach ($index as $s => $ps) {
            $s = $this->expandPName($s);
            $r[$s] = [];
            foreach ($ps as $p => $os) {
                $p = $this->expandPName($p);
                if (!is_array($os)) {
                    $os = [$os];
                }
                foreach ($os as $i => $o) {
                    if (!is_array($o)) {
                        $o_val = $this->expandPName($o);
                        $o_type = preg_match('/^[a-z]+\:[^\s\<\>]+$/si', $o_val) ? 'uri' : 'literal';
                        $o = ['value' => $o_val, 'type' => $o_type];
                    }
                    $os[$i] = $o;
                }
                $r[$s][$p] = $os;
            }
        }

        return $r;
    }

    public function calcURI($path, $base = '')
    {
        /* quick check */
        if (preg_match("/^[a-z0-9\_]+\:/i", $path)) {/* abs path or bnode */
            return $path;
        }
        if (preg_match('/^\$\{.*\}/', $path)) {/* placeholder, assume abs URI */
            return $path;
        }
        if (preg_match("/^\/\//", $path)) {/* net path, assume http */
            return 'http:'.$path;
        }
        /* other URIs */
        $base = $base ?: $this->base;
        $base = preg_replace('/\#.*$/', '', $base);
        if (true === $path) {/* empty (but valid) URIref via turtle parser: <> */
            return $base;
        }
        $path = preg_replace("/^\.\//", '', $path);
        $root = preg_match('/(^[a-z0-9]+\:[\/]{1,3}[^\/]+)[\/|$]/i', $base, $m) ? $m[1] : $base; /* w/o trailing slash */
        $base .= ($base == $root) ? '/' : '';
        if (preg_match('/^\//', $path)) {/* leading slash */
            return $root.$path;
        }
        if (!$path) {
            return $base;
        }
        if (preg_match('/^([\#\?])/', $path, $m)) {
            return preg_replace('/\\'.$m[1].'.*$/', '', $base).$path;
        }
        if (preg_match('/^(\&)(.*)$/', $path, $m)) {/* not perfect yet */
            return preg_match('/\?/', $base) ? $base.$m[1].$m[2] : $base.'?'.$m[2];
        }
        if (preg_match("/^[a-z0-9]+\:/i", $path)) {/* abs path */
            return $path;
        }
        /* rel path: remove stuff after last slash */
        $base = substr($base, 0, strrpos($base, '/') + 1);
        /* resolve ../ */
        while (preg_match('/^(\.\.\/)(.*)$/', $path, $m)) {
            $path = $m[2];
            $base = ($base == $root.'/') ? $base : preg_replace('/^(.*\/)[^\/]+\/$/', '\\1', $base);
        }

        return $base.$path;
    }

    public function calcBase($path)
    {
        $r = $path;
        $r = preg_replace('/\#.*$/', '', $r); /* remove hash */
        $r = preg_replace('/^\/\//', 'http://', $r); /* net path (//), assume http */
        if (preg_match('/^[a-z0-9]+\:/', $r)) {/* scheme, abs path */
            while (preg_match('/^(.+\/)(\.\.\/.*)$/U', $r, $m)) {
                $r = $this->calcURI($m[1], $m[2]);
            }

            return $r;
        }

        return 'file://'.realpath($r); /* real path */
    }

    public function getResource($uri, $store_or_props = '')
    {
        $res = ARC2::getResource($this->a);
        $res->setURI($uri);
        if (is_array($store_or_props)) {
            $res->setProps($store_or_props);
        } else {
            $res->setStore($store_or_props);
        }

        return $res;
    }

    public function toIndex($v)
    {
        if (is_array($v)) {
            if (isset($v[0]) && isset($v[0]['s'])) {
                return ARC2::getSimpleIndex($v, 0);
            }

            return $v;
        }
        $parser = ARC2::getRDFParser($this->a);
        if ($v && !preg_match('/\s/', $v)) {/* assume graph URI */
            $parser->parse($v);
        } else {
            $parser->parse('', $v);
        }

        return $parser->getSimpleIndex(0);
    }

    public function toTriples($v)
    {
        if (is_array($v)) {
            if (isset($v[0]) && isset($v[0]['s'])) {
                return $v;
            }

            return ARC2::getTriplesFromIndex($v);
        }
        $parser = ARC2::getRDFParser($this->a);
        if ($v && !preg_match('/\s/', $v)) {/* assume graph URI */
            $parser->parse($v);
        } else {
            $parser->parse('', $v);
        }

        return $parser->getTriples();
    }

    public function toNTriples($v, $ns = '', $raw = 0)
    {
        ARC2::inc('NTriplesSerializer');
        if (!$ns) {
            $ns = isset($this->a['ns']) ? $this->a['ns'] : [];
        }
        $ser = new ARC2_NTriplesSerializer(array_merge($this->a, ['ns' => $ns]), $this);

        return (isset($v[0]) && isset($v[0]['s'])) ? $ser->getSerializedTriples($v, $raw) : $ser->getSerializedIndex($v, $raw);
    }

    public function toTurtle($v, $ns = '', $raw = 0)
    {
        ARC2::inc('TurtleSerializer');
        if (!$ns) {
            $ns = isset($this->a['ns']) ? $this->a['ns'] : [];
        }
        $ser = new ARC2_TurtleSerializer(array_merge($this->a, ['ns' => $ns]), $this);

        return (isset($v[0]) && isset($v[0]['s'])) ? $ser->getSerializedTriples($v, $raw) : $ser->getSerializedIndex($v, $raw);
    }

    public function toRDFXML($v, $ns = '', $raw = 0)
    {
        ARC2::inc('RDFXMLSerializer');
        if (!$ns) {
            $ns = isset($this->a['ns']) ? $this->a['ns'] : [];
        }
        $ser = new ARC2_RDFXMLSerializer(array_merge($this->a, ['ns' => $ns]), $this);

        return (isset($v[0]) && isset($v[0]['s'])) ? $ser->getSerializedTriples($v, $raw) : $ser->getSerializedIndex($v, $raw);
    }

    public function toRDFJSON($v, $ns = '')
    {
        ARC2::inc('RDFJSONSerializer');
        if (!$ns) {
            $ns = isset($this->a['ns']) ? $this->a['ns'] : [];
        }
        $ser = new ARC2_RDFJSONSerializer(array_merge($this->a, ['ns' => $ns]), $this);

        return (isset($v[0]) && isset($v[0]['s'])) ? $ser->getSerializedTriples($v) : $ser->getSerializedIndex($v);
    }

    public function toRSS10($v, $ns = '')
    {
        ARC2::inc('RSS10Serializer');
        if (!$ns) {
            $ns = isset($this->a['ns']) ? $this->a['ns'] : [];
        }
        $ser = new ARC2_RSS10Serializer(array_merge($this->a, ['ns' => $ns]), $this);

        return (isset($v[0]) && isset($v[0]['s'])) ? $ser->getSerializedTriples($v) : $ser->getSerializedIndex($v);
    }

    public function toLegacyXML($v, $ns = '')
    {
        ARC2::inc('LegacyXMLSerializer');
        if (!$ns) {
            $ns = isset($this->a['ns']) ? $this->a['ns'] : [];
        }
        $ser = new ARC2_LegacyXMLSerializer(array_merge($this->a, ['ns' => $ns]), $this);

        return $ser->getSerializedArray($v);
    }

    public function toLegacyJSON($v, $ns = '')
    {
        ARC2::inc('LegacyJSONSerializer');
        if (!$ns) {
            $ns = isset($this->a['ns']) ? $this->a['ns'] : [];
        }
        $ser = new ARC2_LegacyJSONSerializer(array_merge($this->a, ['ns' => $ns]), $this);

        return $ser->getSerializedArray($v);
    }

    public function toLegacyHTML($v, $ns = '')
    {
        ARC2::inc('LegacyHTMLSerializer');
        if (!$ns) {
            $ns = isset($this->a['ns']) ? $this->a['ns'] : [];
        }
        $ser = new ARC2_LegacyHTMLSerializer(array_merge($this->a, ['ns' => $ns]), $this);

        return $ser->getSerializedArray($v);
    }

    public function toHTML($v, $ns = '', $label_store = '')
    {
        ARC2::inc('MicroRDFSerializer');
        if (!$ns) {
            $ns = isset($this->a['ns']) ? $this->a['ns'] : [];
        }
        $conf = array_merge($this->a, ['ns' => $ns]);
        if ($label_store) {
            $conf['label_store'] = $label_store;
        }
        $ser = new ARC2_MicroRDFSerializer($conf, $this);

        return (isset($v[0]) && isset($v[0]['s'])) ? $ser->getSerializedTriples($v) : $ser->getSerializedIndex($v);
    }

    public function getFilledTemplate($t, $vals, $g = '')
    {
        $parser = ARC2::getTurtleParser();
        $parser->parse($g, $this->getTurtleHead().$t);

        return $parser->getSimpleIndex(0, $vals);
    }

    public function getTurtleHead()
    {
        $r = '';
        $ns = $this->v('ns', [], $this->a);
        foreach ($ns as $k => $v) {
            $r .= '@prefix '.$k.': <'.$v."> .\n";
        }

        return $r;
    }

    public function completeQuery($q, $ns = '')
    {
        if (!$ns) {
            $ns = isset($this->a['ns']) ? $this->a['ns'] : [];
        }
        $added_prefixes = [];
        $prologue = '';
        foreach ($ns as $k => $v) {
            $k = rtrim($k, ':');
            if (in_array($k, $added_prefixes)) {
                continue;
            }
            if (preg_match('/(^|\s)'.$k.':/s', $q) && !preg_match('/PREFIX\s+'.$k.'\:/is', $q)) {
                $prologue .= "\n".'PREFIX '.$k.': <'.$v.'>';
            }
            $added_prefixes[] = $k;
        }

        return $prologue."\n".$q;
    }

    public function toUTF8($str)
    {
        return $this->adjust_utf8 ? ARC2::toUTF8($str) : $str;
    }

    public function toDataURI($str)
    {
        return 'data:text/plain;charset=utf-8,'.rawurlencode($str);
    }

    public function fromDataURI($str)
    {
        return str_replace('data:text/plain;charset=utf-8,', '', rawurldecode($str));
    }

    /* prevent SQL injections via SPARQL REGEX */

    public function checkRegex($str)
    {
        return addslashes($str); // @@todo extend
    }

    /* Microdata methods */

    public function getMicrodataAttrs($id, $type = '')
    {
        $type = $type ? $this->expandPName($type) : $this->expandPName('owl:Thing');

        return 'itemscope="" itemtype="'.htmlspecialchars($type).'" itemid="'.htmlspecialchars($id).'"';
    }

    public function mdAttrs($id, $type = '')
    {
        return $this->getMicrodataAttrs($id, $type);
    }

    /* central DB query hook */

    public function getDBObjectFromARC2Class($con = null)
    {
        if (null == $this->db_object) {
            if (false === class_exists('\\ARC2\\Store\\Adapter\\AdapterFactory')) {
                require __DIR__.'/src/ARC2/Store/Adapter/AdapterFactory.php';
            }
            if (false == isset($this->a['db_adapter'])) {
                $this->a['db_adapter'] = 'pdo';
                $this->a['db_pdo_protocol'] = 'mysql';
            }
            $factory = new \ARC2\Store\Adapter\AdapterFactory();
            $this->db_object = $factory->getInstanceFor($this->a['db_adapter'], $this->a);
            if ($con) {
                $this->db_object->connect($con);
            } else {
                $this->db_object->connect();
            }
        }

        return $this->db_object;
    }

    /**
     * Shortcut method to create an RDF/XML backup dump from an RDF Store object.
     */
    public function backupStoreData($store, $target_path, $offset = 0)
    {
        $limit = 10;
        $q = '
      SELECT DISTINCT ?s WHERE {
        ?s ?p ?o .
      }
      ORDER BY ?s
      LIMIT '.$limit.'
      '.($offset ? 'OFFSET '.$offset : '').'
    ';
        $rows = $store->query($q, 'rows');
        $tc = count($rows);
        $full_tc = $tc + $offset;
        $mode = $offset ? 'ab' : 'wb';
        $fp = fopen($target_path, $mode);
        foreach ($rows as $row) {
            $index = $store->query('DESCRIBE <'.$row['s'].'>', 'raw');
            if ($index) {
                $doc = $this->toRDFXML($index);
                fwrite($fp, $doc."\n\n");
            }
        }
        fclose($fp);
        if (10 == $tc) {
            set_time_limit(300);
            $this->backupStoreData($store, $target_path, $offset + $limit);
        }

        return $full_tc;
    }
}
