<?php
/**
 * ARC2 Remote RDF Store.
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license W3C Software License and GPL
 *
 * @version 2010-11-16
 */
ARC2::inc('Class');

class ARC2_RemoteStore extends ARC2_Class
{
    public int $is_remote;
    public $reader;

    /**
     * @var array<mixed>
     */
    public array $resource_labels;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
        $this->is_remote = 1;
    }

    public function __init()
    {
        parent::__init();
    }

    public function isSetUp()
    {
        return 1;
    }

    public function setUp(): void
    {
    }

    public function killDBProcesses()
    {
    }

    public function reset()
    {
    }

    public function drop()
    {
    }

    public function insert($doc, $g, $keep_bnode_ids = 0)
    {
        return $this->query('INSERT INTO <'.$g.'> { '.$this->toNTriples($doc, '', 1).' }');
    }

    public function delete($doc, $g)
    {
        if (!$doc) {
            return $this->query('DELETE FROM <'.$g.'>');
        } else {
            return $this->query('DELETE FROM <'.$g.'> { '.$this->toNTriples($doc, '', 1).' }');
        }
    }

    public function replace($doc, $g, $doc_2)
    {
        return [$this->delete($doc, $g), $this->insert($doc_2, $g)];
    }

    public function query($q, $result_format = '', $src = '', $keep_bnode_ids = 0, $log_query = 0)
    {
        if ($log_query) {
            $this->logQuery($q);
        }
        ARC2::inc('SPARQLPlusParser');
        $p = new ARC2_SPARQLPlusParser($this->a, $this);
        $p->parse($q, $src);
        $infos = $p->getQueryInfos();
        $t1 = ARC2::mtime();
        if (!$errs = $p->getErrors()) {
            $qt = $infos['query']['type'];
            $r = ['query_type' => $qt, 'result' => $this->runQuery($q, $qt, $infos)];
        } else {
            $r = ['result' => ''];
        }
        $t2 = ARC2::mtime();
        $r['query_time'] = $t2 - $t1;
        /* query result */
        if ('raw' == $result_format) {
            return $r['result'];
        }
        if ('rows' == $result_format) {
            return $this->v('rows', [], $r['result']);
        }
        if ('row' == $result_format) {
            if (!isset($r['result']['rows'])) {
                return [];
            }

            return $r['result']['rows'] ? $r['result']['rows'][0] : [];
        }

        return $r;
    }

    public function runQuery($q, $qt = '', $infos = '')
    {
        /* ep */
        $ep = $this->v('remote_store_endpoint', 0, $this->a);
        if (!$ep) {
            return false;
        }
        /* prefixes */
        $q = $this->completeQuery($q);
        /* custom handling */
        $mthd = 'run'.$this->camelCase($qt).'Query';
        if (method_exists($this, $mthd)) {
            return $this->$mthd($q, $infos);
        }
        /* http verb */
        $mthd = in_array($qt, ['load', 'insert', 'delete']) ? 'POST' : 'GET';
        /* reader */
        ARC2::inc('Reader');
        $reader = new ARC2_Reader($this->a, $this);
        $reader->setAcceptHeader('Accept: application/sparql-results+xml; q=0.9, application/rdf+xml; q=0.9, */*; q=0.1');
        if ('GET' == $mthd) {
            $url = $ep;
            $url .= strpos($ep, '?') ? '&' : '?';
            $url .= 'query='.urlencode($q);
            if ($k = $this->v('store_read_key', '', $this->a)) {
                $url .= '&key='.urlencode($k);
            }
        } else {
            $url = $ep;
            $reader->setHTTPMethod($mthd);
            $reader->setCustomHeaders('Content-Type: application/x-www-form-urlencoded');
            $suffix = ($k = $this->v('store_write_key', '', $this->a)) ? '&key='.rawurlencode($k) : '';
            $reader->setMessageBody('query='.rawurlencode($q).$suffix);
        }
        $to = $this->v('remote_store_timeout', 0, $this->a);
        $reader->activate($url, '', 0, $to);
        $format = $reader->getFormat();
        $resp = '';
        while ($d = $reader->readStream()) {
            $resp .= $this->toUTF8($d);
        }
        $reader->closeStream();
        $ers = $reader->getErrors();
        $this->a['reader_auth_infos'] = $reader->getAuthInfos();
        unset($this->reader);
        if ($ers) {
            return ['errors' => $ers];
        }
        $mappings = ['rdfxml' => 'RDFXML', 'sparqlxml' => 'SPARQLXMLResult', 'turtle' => 'Turtle'];
        if (!$format || !isset($mappings[$format])) {
            return $resp;
            // return $this->addError('No parser available for "' . $format . '" SPARQL result');
        }
        /* format parser */
        $suffix = $mappings[$format].'Parser';
        ARC2::inc($suffix);
        $cls = 'ARC2_'.$suffix;
        $parser = new $cls($this->a, $this);
        $parser->parse($ep, $resp);
        /* ask|load|insert|delete */
        if (in_array($qt, ['ask', 'load', 'insert', 'delete'])) {
            $bid = $parser->getBooleanInsertedDeleted();
            if ('ask' == $qt) {
                $r = $bid['boolean'];
            } else {
                $r = $bid;
            }
        }
        /* select */
        elseif (('select' == $qt) && !method_exists($parser, 'getRows')) {
            $r = $resp;
        } elseif ('select' == $qt) {
            $r = ['rows' => $parser->getRows(), 'variables' => $parser->getVariables()];
        }
        /* any other */
        else {
            $r = $parser->getSimpleIndex(0);
        }
        unset($parser);

        return $r;
    }

    public function optimizeTables()
    {
    }

    public function getResourceLabel($res, $unnamed_label = 'An unnamed resource')
    {
        if (!isset($this->resource_labels)) {
            $this->resource_labels = [];
        }
        if (isset($this->resource_labels[$res])) {
            return $this->resource_labels[$res];
        }
        if (!preg_match('/^[a-z0-9\_]+\:[^\s]+$/si', $res)) {
            return $res;
        } /* literal */
        $r = '';
        if (preg_match('/^\_\:/', $res)) {
            return $unnamed_label;
        }
        $row = $this->query('SELECT ?o WHERE { <'.$res.'> ?p ?o . FILTER(REGEX(str(?p), "(label|name)$", "i"))}', 'row');
        if ($row) {
            $r = $row['o'];
        } else {
            $r = preg_replace("/^(.*[\/\#])([^\/\#]+)$/", '\\2', str_replace('#self', '', $res));
            $r = str_replace('_', ' ', $r);
            $r = preg_replace_callback('/([a-z])([A-Z])/', function ($matches) {
                return $matches[1].' '.strtolower($matches[2]);
            }, $r);
        }
        $this->resource_labels[$res] = $r;

        return $r;
    }

    public function getDomains($p)
    {
        $r = [];
        foreach ($this->query('SELECT DISTINCT ?type WHERE {?s <'.$p.'> ?o ; a ?type . }', 'rows') as $row) {
            $r[] = $row['type'];
        }

        return $r;
    }
}
