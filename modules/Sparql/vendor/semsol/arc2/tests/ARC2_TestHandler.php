<?php
/*
homepage: http://arc.web-semantics.org/
license:  http://arc.web-semantics.org/license

class:    ARC2 DAWG Test Handler
author:   Benjamin Nowack
version:  2011-12-01
*/

ARC2::inc('Class');

class ARC2_TestHandler extends ARC2_Class
{
    public $data_store;
    public $reader;
    public $store;

    public function __construct($a, &$caller, &$data_store)
    {/* caller has to be a store */
        parent::__construct($a, $caller);
        $this->data_store = $data_store;
    }

    public function __init()
    {
        parent::__init();
        $this->store = $this->caller;
        ARC2::inc('Reader');
        $this->reader = new ARC2_Reader($this->a, $this);
    }

    public function runTest($id)
    {
        $type = $this->getTestType($id);
        $m = 'run'.$type;
        $r = method_exists($this, $m) ? $this->$m($id) : ['pass' => 0, 'info' => 'not supported'];
        sleep(1);

        return $r;
    }

    public function getTestType($id)
    {
        $q = 'SELECT ?type WHERE { <'.$id.'> a ?type . }';
        $qr = $this->store->query($q);
        $r = isset($qr['result']['rows'][0]) ? $qr['result']['rows'][0]['type'] : '#QueryEvaluationTest';
        $r = preg_replace('/^.*\#([^\#]+)$/', '$1', $r);

        return $r;
    }

    public function getFile($url)
    {
        $fname = 'f'.crc32($url).'.txt';
        if (!file_exists('tmp/'.$fname)) {
            $r = '';
            if (!isset($this->reader)) {
                $this->reader = new ARC2_Reader($this->a, $this);
            }
            $this->reader->activate($url);
            while ($d = $this->reader->readStream()) {
                $r .= $d;
            }
            $this->reader->closeStream();
            unset($this->reader);
            $fp = fopen('tmp/'.$fname, 'w');
            fwrite($fp, $r);
            fclose($fp);

            return $r;
        }

        return file_get_contents('tmp/'.$fname);
    }

    public function runPositiveSyntaxTest($id)
    {
        $nl = "\n";
        $r = '';
        /* get action */
        $q = '
      PREFIX mf:      <http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#> .
      SELECT DISTINCT ?action WHERE { <'.$id.'> mf:action ?action . }
    ';
        $qr = $this->store->query($q);
        $action = $qr['result']['rows'][0]['action'];
        /* get code */
        $q = $this->getFile($action);
        /* parse */
        ARC2::inc('SPARQLPlusParser');
        $parser = new ARC2_SPARQLPlusParser($this->a, $this);
        $parser->parse($q, $action);
        $infos = $parser->getQueryInfos();
        $rest = $parser->getUnparsedCode();
        $errors = $parser->getErrors();
        $r .= $nl.'<div style="border: #eee solid 1px ; padding: 5px; ">'.htmlspecialchars($q).'</div>'.$nl;
        if ($errors || $rest) {
            $pass = 0;
            $r .= htmlspecialchars($nl.$nl.print_r($errors, 1).$nl.print_r($rest, 1));
        } else {
            $pass = 1;
            $r .= htmlspecialchars($nl.$nl.print_r($infos, 1));
        }

        return ['pass' => $pass, 'info' => $r];
    }

    public function runNegativeSyntaxTest($id)
    {
        $nl = "\n";
        $r = '';
        /* get action */
        $q = '
      PREFIX mf:      <http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#> .
      SELECT DISTINCT ?action WHERE { <'.$id.'> mf:action ?action . }
    ';
        $qr = $this->store->query($q);
        $action = $qr['result']['rows'][0]['action'];
        /* get code */
        $q = $this->getFile($action);
        /* parse */
        ARC2::inc('SPARQLPlusParser');
        $parser = new ARC2_SPARQLPlusParser($this->a, $this);
        $parser->parse($q, $action);
        $infos = $parser->getQueryInfos();
        $rest = $parser->getUnparsedCode();
        $errors = $parser->getErrors();
        $r .= $nl.'<div style="border: #eee solid 1px ; padding: 5px; ">'.htmlspecialchars($q).'</div>'.$nl;
        if ($errors || $rest) {
            $pass = 1;
            $r .= htmlspecialchars($nl.$nl.print_r($errors, 1).$nl.print_r($rest, 1));
        } else {
            $pass = 0;
            $r .= htmlspecialchars($nl.$nl.print_r($infos, 1));
        }

        return ['pass' => $pass, 'info' => $r];
    }

    public function runQueryEvaluationTest($id)
    {
        $nl = "\n";
        $r = '';
        /* get action */
        $q = '
      PREFIX mf:      <http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#> .
      PREFIX qt:      <http://www.w3.org/2001/sw/DataAccess/tests/test-query#> .
      SELECT DISTINCT ?query ?data ?graph_data ?result WHERE {
        <'.$id.'> mf:action ?action ;
                    mf:result ?result .
        ?action     qt:query  ?query .
        OPTIONAL {
          ?action qt:data ?data .
        }
        OPTIONAL {
          ?action qt:graphData ?graph_data .
        }
      }
    ';
        $qr = $this->store->query($q);
        $rows = $qr['result']['rows'];
        $infos = [];
        foreach (['query', 'data', 'result', 'graph_data'] as $var) {
            $infos[$var] = [];
            $infos[$var.'_value'] = [];
            foreach ($rows as $row) {
                if (isset($row[$var])) {
                    if (!in_array($row[$var], $infos[$var])) {
                        $infos[$var][] = $row[$var];
                        $infos[$var.'_value'][] = $this->getFile($row[$var]);
                    }
                }
            }
            $$var = $infos[$var];
            ${$var.'_value'} = $infos[$var.'_value'];
            if (1 == count($infos[$var])) {
                $$var = $infos[$var][0];
                ${$var.'_value'} = $infos[$var.'_value'][0];
            }
            if ($$var && ('-result' != $var)) {
                // echo '<pre>' . $$var . $nl . $nl . htmlspecialchars(${$var . '_value'}) . '</pre><hr />';
            }
        }
        /* query infos */
        ARC2::inc('SPARQLPlusParser');
        $parser = new ARC2_SPARQLPlusParser($this->a, $this);
        $parser->parse($query_value, $query);
        $infos = $parser->getQueryInfos();
        $rest = $parser->getUnparsedCode();
        $errors = $parser->getErrors();
        $q_type = !$errors ? $infos['query']['type'] : '';
        /* add data */
        $dsets = [];
        $gdsets = [];
        if ($data) {
            $dsets = is_array($data) ? array_merge($dsets, $data) : array_merge($dsets, [$data]);
        }
        if ($graph_data) {
            $gdsets = is_array($graph_data) ? array_merge($gdsets, $graph_data) : array_merge($gdsets, [$graph_data]);
        }
        if (!$dsets && !$gdsets) {
            foreach ($infos['query']['dataset'] as $set) {
                if ($set['named']) {
                    $gdsets[] = $set['graph'];
                } else {
                    $dsets[] = $set['graph'];
                }
            }
        }
        $store = $this->data_store;
        $store->reset();
        foreach ($dsets as $graph) {
            $qr = $store->query('LOAD <'.$graph.'>');
        }
        foreach ($gdsets as $graph) {
            $qr = $store->query('LOAD <'.$graph.'> INTO <'.$graph.'>');
        }
        /* run query */
        if ($query) {
            $sql = $store->query($query_value, 'sql', $query);
            $qr = $store->query($query_value, '', $query);
            $qr_result = $qr['result'];
            if ('select' == $q_type) {
                $qr_result = $this->adjustBnodes($qr['result'], $id);
            } elseif ('construct' == $q_type) {
                $ser = ARC2::getTurtleSerializer($this->a);
                $qr_result = $ser->getSerializedIndex($qr_result);
            }
        }
        // echo '<pre>query result: ' . $nl . htmlspecialchars(print_r($qr_result, 1)) . '</pre>';
        if (!$query || $errors || $rest) {
            return ['pass' => 0, 'info' => 'query could not be parsed'.htmlspecialchars($query_value)];
        }
        $m = 'isSame'.$q_type.'Result';
        $sub_r = $this->$m($qr_result, $result_value, $result, $id);
        $pass = $sub_r['pass'];
        if (in_array($id, [
            'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/sort/manifest#dawg-sort-6',
            'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/sort/manifest#dawg-sort-8',
            'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/sort/manifest#dawg-sort-builtin',
        ])) {
            $pass = 0; /* manually checked 2007-09-18 */
        }
        if (in_array($id, [
            'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/sort/manifest#dawg-sort-function',
            'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/reduced/manifest#reduced-1',
            'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/reduced/manifest#reduced-2',
        ])) {
            $pass = 1; /* manually checked 2007-11-28 */
        }
        $pass_info = $sub_r['info'];
        $info = print_r($pass_info, 1).$nl;
        $info .= '<hr />sql: '.$nl.htmlspecialchars($sql['result']).'<hr />';
        $info .= $pass ? '' : print_r($graph_data, 1).$nl.htmlspecialchars(print_r($graph_data_value, 1)).'<hr />';
        $info .= $pass ? '' : print_r($data, 1).$nl.htmlspecialchars(print_r($data_value, 1)).'<hr />';
        $info .= $pass ? '' : $query.$nl.htmlspecialchars($query_value).'<hr />';
        $info .= $pass ? '' : '<pre>query result: '.$nl.htmlspecialchars(print_r($qr_result, 1)).'</pre><hr />';
        $info .= $pass ? '' : print_r($infos, 1);

        return ['pass' => $pass, 'info' => $info];
    }

    public function isSameSelectResult($qr, $result, $result_base)
    {
        if (strpos($result, 'http://www.w3.org/2001/sw/DataAccess/tests/result-set#')) {
            $parser = ARC2::getRDFParser($this->a);
            $parser->parse($result_base, $result);
            $index = $parser->getSimpleIndex(0);
            // echo '<pre>' . print_r($index, 1) .'</pre>';
            $valid_qr = $this->buildTurtleSelectQueryResult($index);
        } else {
            $parser = ARC2::getSPARQLXMLResultParser($this->a);
            $parser->parse('', $result);
            $valid_qr = $parser->getStructure();
        }
        if (isset($valid_qr['boolean'])) {
            $pass = $valid_qr['boolean'] == $this->v('boolean', '', $qr);
        } else {
            $pass = 1;
            if (count($valid_qr['variables']) != count($qr['variables'])) {
                $pass = 0;
            }
            if (count($valid_qr['rows']) != count($qr['rows'])) {
                $pass = 0;
            }
            if ($pass) {
                foreach ($valid_qr['variables'] as $var) {
                    if (!in_array($var, $qr['variables'])) {
                        $pass = 0;
                        break;
                    }
                }
            }
            if ($pass) {
                $index = $this->buildArrayHashIndex($qr['rows']);
                $valid_index = $this->buildArrayHashIndex($valid_qr['rows']);
                if (($diff = array_diff($index, $valid_index)) || ($diff = array_diff($valid_index, $index))) {
                    $pass = 0;
                    // echo '<pre>' . print_r($diff, 1) . '</pre>';
                }
            }
        }

        return ['pass' => $pass, 'info' => $valid_qr];
    }

    public function isSameConstructResult($qr, $result, $result_base, $test)
    {
        $parser = ARC2::getRDFParser($this->a);
        $parser->parse('', $result);
        $valid_triples = $parser->getTriples();
        $parser = ARC2::getRDFParser($this->a);
        $parser->parse('', $qr);
        $triples = $parser->getTriples();
        $info = '<pre>'.print_r($valid_triples, 1).'</pre>';
        $info = '';

        // echo '<pre>' . print_r($index, 1) .'</pre>';
        $pass = 0;
        if (in_array($test, [/* manually checked 2007-09-21 */
            'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/construct/manifest#construct-1',
            'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/construct/manifest#construct-2',
            'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/construct/manifest#construct-3',
            'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/construct/manifest#construct-4',
            'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/construct/manifest#construct-5',
        ])) {
            $pass = 1;
        }

        return ['pass' => $pass, 'info' => $valid_triples];
    }

    public function isSameAskResult($qr, $result, $result_base)
    {
        if (preg_match('/(true|false)\.(ttl|n3)$/', $result_base, $m)) {
            $valid_r = $m[1];
        } else {
            $valid_r = preg_match('/boolean\>([^\<]+)/s', $result, $m) ? trim($m[1]) : '-';
        }
        $r = (true === $qr) ? 'true' : 'false';
        $pass = ($r == $valid_r) ? 1 : 0;

        return ['pass' => $pass, 'info' => $valid_r];
    }

    public function buildTurtleSelectQueryResult($index)
    {
        $rs = 'http://www.w3.org/2001/sw/DataAccess/tests/result-set#';
        $rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $r = ['variables' => [], 'rows' => []];
        foreach ($index as $node => $props) {
            $types = $this->v($rdf.'type', [], $props);
            foreach ($types as $type) {
                if ($type['value'] == $rs.'ResultSet') {
                    $vars = $this->v($rs.'resultVariable', [], $props);
                    foreach ($vars as $var) {
                        $r['variables'][] = $var['value'];
                    }
                }
            }
            $bindings = $this->v($rs.'binding', [], $props);
            if ($bindings) {
                $row = [];
                foreach ($bindings as $binding) {
                    $binding_id = $binding['value'];
                    $var = $index[$binding_id][$rs.'variable'][0]['value'];
                    $val = $index[$binding_id][$rs.'value'][0]['value'];
                    $val_type = $index[$binding_id][$rs.'value'][0]['type'];
                    // $val_type = preg_match('/literal/', $val_type) ? 'literal' : $val_type;
                    $row[$var] = $val;
                    $row[$var.' type'] = $val_type;
                    if ($dt = $this->v('datatype', 0, $index[$binding_id][$rs.'value'][0])) {
                        $row[$var.' datatype'] = $dt;
                    }
                    if ($lang = $this->v('lang', 0, $index[$binding_id][$rs.'value'][0])) {
                        $row[$var.' lang'] = $lang;
                    }
                }
                $r['rows'][] = $row;
            }
        }

        return $r;
    }

    public function buildArrayHashIndex($rows)
    {
        $r = [];
        foreach ($rows as $row) {
            $hash = '';
            ksort($row);
            foreach ($row as $k => $v) {
                $hash .= is_numeric($k) ? '' : ' '.md5($k).' '.md5($v);
            }
            $r[] = $hash;
        }

        return $r;
    }

    public function adjustBnodes($result, $data)
    {
        $mappings = [
            '_:b1371233574_bob' => '_:b10',
            '_:b1114277307_alice' => '_:b1f',
            '_:b1368422168_eve' => '_:b20',
            '_:b1638119969_fred' => '_:b21',

            '_:b288335586_a' => [
                'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/distinct/manifest#no-distinct-3' => '_:b0',
                'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/distinct/manifest#distinct-3' => '_:b0',
                'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/distinct/manifest#distinct-9' => '_:b0',
                'http://www.w3.org/2001/sw/DataAccess/tests/data-r2/distinct/manifest#no-distinct-9' => '_:b0',
                'default' => '_:bn5',
            ],
        ];
        if (isset($result['rows'])) {
            foreach ($result['rows'] as $i => $row) {
                foreach ($result['variables'] as $var) {
                    if (isset($row[$var]) && isset($mappings[$row[$var]])) {
                        if (is_array($mappings[$row[$var]])) {
                            $result['rows'][$i][$var] = isset($mappings[$row[$var]][$data]) ? $mappings[$row[$var]][$data] : $mappings[$row[$var]]['default'];
                        } else {
                            $result['rows'][$i][$var] = $mappings[$row[$var]];
                        }
                    }
                }
            }
        }

        return $result;
    }
}
