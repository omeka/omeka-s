<?php
/**
 * ARC2 SPARQLScript Processor.
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license W3C Software License and GPL
 *
 * @version 2010-11-16
 */
ARC2::inc('Class');

class ARC2_SPARQLScriptProcessor extends ARC2_Class
{
    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->max_operations = $this->v('sparqlscript_max_operations', 0, $this->a);
        $this->max_queries = $this->v('sparqlscript_max_queries', 0, $this->a);
        $this->return = 0;
        $this->script_hash = '';
        $this->env = [
            'endpoint' => '',
            'vars' => [],
            'output' => '',
            'operation_count' => 0,
            'query_count' => 0,
            'query_log' => [],
        ];
    }

    public function reset()
    {
        $this->__init();
    }

    public function processScript($s)
    {
        $this->script_hash = abs(crc32($s));
        $parser = $this->getParser();
        $parser->parse($s);
        $blocks = $parser->getScriptBlocks();
        if ($parser->getErrors()) {
            return 0;
        }
        foreach ($blocks as $block) {
            $this->processBlock($block);
            if ($this->return) {
                return 0;
            }
            if ($this->getErrors()) {
                return 0;
            }
        }
    }

    public function getResult()
    {
        if ($this->return) {
            return $this->getVarValue('__return_value__');
        } else {
            return $this->env['output'];
        }
    }

    public function getParser()
    {
        ARC2::inc('SPARQLScriptParser');

        return new ARC2_SPARQLScriptParser($this->a, $this);
    }

    public function setVar($name, $val, $type = 'literal', $meta = '')
    {
        /* types: literal, var, rows, bool, doc, http_response, undefined, ? */
        $this->env['vars'][$name] = [
            'value_type' => $type,
            'value' => $val,
            'meta' => $meta ? $meta : [],
        ];
    }

    public function getVar($name)
    {
        return isset($this->env['vars'][$name]) ? $this->env['vars'][$name] : '';
    }

    public function getVarValue($name)
    {
        return ($v = $this->getVar($name)) ? (isset($v['value']) ? $v['value'] : $v) : '';
    }

    public function replacePlaceholders($val, $context = '', $return_string = 1, $loop = 0)
    {
        do {
            $old_val = $val;
            if (preg_match_all('/(\{(?:[^{}]+|(?R))*\})/', $val, $m)) {
                foreach ($m[1] as $match) {
                    if (!str_contains($val, '$'.$match)) {/* just some container brackets, recurse */
                        $val = str_replace($match, '{'.$this->replacePlaceholders(substr($match, 1, -1), $context, $return_string, $loop + 1).'}', $val);
                    } else {
                        $ph = substr($match, 1, -1);
                        $sub_val = $this->getPlaceholderValue($ph);
                        if (is_array($sub_val)) {
                            $sub_val = $this->getArraySerialization($sub_val, $context);
                        }
                        $val = str_replace('${'.$ph.'}', $sub_val, $val);
                    }
                }
            }
        } while (($old_val != $val) && ($loop < 10));

        return $val;
    }

    public function getPlaceholderValue($ph)
    {
        /* simple vars */
        if (isset($this->env['vars'][$ph])) {
            return $this->v('value', $this->env['vars'][$ph], $this->env['vars'][$ph]);
        }
        /* GET/POST */
        if (preg_match('/^(GET|POST)\.([^\.]+)(.*)$/', $ph, $m)) {
            $vals = 'GET' == strtoupper($m[1]) ? $_GET : $POST;
            $r = isset($vals[$m[2]]) ? $vals[$m[2]] : '';

            return $m[3] ? $this->getPropertyValue(['value' => $r, 'value_type' => '?'], ltrim($m[3], '.')) : $r;
        }
        /* NOW */
        if (preg_match('/^NOW(.*)$/', $ph, $m)) {
            $rest = $m[1];
            /* may have sub-phs */
            $rest = $this->replacePlaceholders($rest);
            $r_struct = [
                'y' => date('Y'),
                'mo' => date('m'),
                'd' => date('d'),
                'h' => date('H'),
                'mi' => date('i'),
                's' => date('s'),
            ];
            if (preg_match('/(\+|\-)\s*([0-9]+)(y|mo|d|h|mi|s)[a-z]*(.*)/is', trim($rest), $m2)) {
                eval('$r_struct[$m2[3]] '.$m2[1].'= (int)'.$m2[2].';');
                $rest = $m2[4];
            }
            $uts = mktime($r_struct['h'], $r_struct['mi'], $r_struct['s'], $r_struct['mo'], $r_struct['d'], $r_struct['y']);
            $uts -= date('Z', $uts); /* timezone offset */
            $r = date('Y-m-d\TH:i:s\Z', $uts);
            if (preg_match('/^\.(.+)$/', $rest, $m)) {
                return $this->getPropertyValue(['value' => $r], $m[1]);
            }

            return $r;
        }
        /* property */
        if (preg_match('/^([^\.]+)\.(.+)$/', $ph, $m)) {
            list($var, $path) = [$m[1], $m[2]];
            if (isset($this->env['vars'][$var])) {
                return $this->getPropertyValue($this->env['vars'][$var], $path);
            }
        }

        return '';
    }

    public function getPropertyValue($obj, $path)
    {
        $val = isset($obj['value']) ? $obj['value'] : $obj;
        $path = $this->replacePlaceholders($path, 'property_value', 0);
        /* reserved */
        if ('size' == $path) {
            if ('rows' == $obj['value_type']) {
                return count($val);
            }
            if ('literal' == $obj['value_type']) {
                return strlen($val);
            }
        }
        if (preg_match('/^replace\([\'\"](\/.*\/[a-z]*)[\'\"],\s*[\'\"](.*)[\'\"]\)$/is', $path, $m)) {
            return @preg_replace($m[1], str_replace('$', '\\', $m[2]), $val);
        }
        if (preg_match('/^match\([\'\"](\/.*\/[a-z]*)[\'\"]\)$/is', $path, $m)) {
            return @preg_match($m[1], $val, $m) ? $m : '';
        }
        if (preg_match('/^urlencode\([\'\"]?(get|post|.*)[\'\"]?\)$/is', $path, $m)) {
            return ('post' == strtolower($m[1])) ? rawurlencode($val) : urlencode($val);
        }
        if (preg_match('/^toDataURI\([^\)]*\)$/is', $path, $m)) {
            return 'data:text/plain;charset=utf-8,'.rawurlencode($val);
        }
        if (preg_match('/^fromDataURI\([^\)]*\)$/is', $path, $m)) {
            return rawurldecode(str_replace('data:text/plain;charset=utf-8,', '', $val));
        }
        if (preg_match('/^toPrettyDate\([^\)]*\)$/is', $path, $m)) {
            $uts = strtotime(preg_replace('/(T|\+00\:00)/', ' ', $val));

            return date('D j M H:i', $uts);
        }
        if (preg_match('/^render\(([^\)]*)\)$/is', $path, $m)) {
            $src_format = trim($m[1], '"\'');

            return $this->render($val, $src_format);
        }
        /* struct */
        if (is_array($val)) {
            if (isset($val[$path])) {
                return $val[$path];
            }
            $exp_path = $this->expandPName($path);
            if (isset($val[$exp_path])) {
                return $val[$exp_path];
            }
            if (preg_match('/^([^\.]+)\.(.+)$/', $path, $m)) {
                list($var, $path) = [$m[1], $m[2]];
                if (isset($val[$var])) {
                    return $this->getPropertyValue(['value' => $val[$var]], $path);
                }
                /* qname */
                $exp_var = $this->expandPName($var);
                if (isset($val[$exp_var])) {
                    return $this->getPropertyValue(['value' => $val[$exp_var]], $path);
                }

                return '';
            }
        }
        /* meta */
        if (preg_match('/^\_/', $path) && isset($obj['meta']) && isset($obj['meta'][substr($path, 1)])) {
            return $obj['meta'][substr($path, 1)];
        }

        return '';
    }

    public function render($val, $src_format = '')
    {
        if ($src_format) {
            $mthd = 'render'.$this->camelCase($src_format);
            if (method_exists($this, $mthd)) {
                return $this->$mthd($val);
            } else {
                return 'No rendering method found for "'.$src_format.'"';
            }
        }

        /* try RDF */
        return $this->getArraySerialization($val);
    }

    public function renderObjects($os)
    {
        $r = '';
        foreach ($os as $o) {
            $r .= $r ? ', ' : '';
            $r .= $o['value'];
        }

        return $r;
    }

    public function getArraySerialization($v, $context)
    {
        $v_type = ARC2::getStructType($v); /* string|array|triples|index */
        $pf = ARC2::getPreferredFormat();
        /* string */
        if ('string' == $v_type) {
            return $v;
        }
        /* simple array (e.g. from SELECT) */
        if ('array' == $v_type) {
            return implode(', ', $v);
            $m = method_exists($this, 'toLegacy'.$pf) ? 'toLegacy'.$pf : 'toLegacyXML';
        }
        /* rdf */
        if (('triples' == $v_type) || ('index' == $v_type)) {
            $m = method_exists($this, 'to'.$pf) ? 'to'.$pf : ('query' == $context ? 'toNTriples' : 'toRDFXML');
        }

        /* else */
        return $this->$m($v);
    }

    public function processBlock($block)
    {
        if ($this->max_operations && ($this->env['operation_count'] >= $this->max_operations)) {
            return $this->addError('Number of '.$this->max_operations.' allowed operations exceeded.');
        }
        if ($this->return) {
            return 0;
        }
        ++$this->env['operation_count'];
        $type = $block['type'];
        $m = 'process'.$this->camelCase($type).'Block';
        if (method_exists($this, $m)) {
            return $this->$m($block);
        }

        return $this->addError('Unsupported block type "'.$type.'"');
    }

    public function processEndpointDeclBlock($block)
    {
        $this->env['endpoint'] = $block['endpoint'];

        return $this->env;
    }

    public function processQueryBlock($block)
    {
        if ($this->max_queries && ($this->env['query_count'] >= $this->max_queries)) {
            return $this->addError('Number of '.$this->max_queries.' allowed queries exceeded.');
        }
        ++$this->env['query_count'];
        $ep_uri = $this->replacePlaceholders($this->env['endpoint'], 'endpoint');
        /* q */
        $prologue = 'BASE <'.$block['base'].'>';
        $q = $this->replacePlaceholders($block['query'], 'query');
        /* prefixes */
        $ns = isset($this->a['ns']) ? array_merge($this->a['ns'], $block['prefixes']) : $block['prefixes'];
        $q = $prologue."\n".$this->completeQuery($q, $ns);
        $this->env['query_log'][] = '('.$ep_uri.') '.$q;
        if ($store = $this->getStore($ep_uri)) {
            $sub_r = $this->v('is_remote', '', $store) ? $store->query($q, '', $ep_uri) : $store->query($q);
            /* ignore socket errors */
            if (($errs = $this->getErrors()) && preg_match('/socket/', $errs[0])) {
                $this->warnings[] = $errs[0];
                $this->errors = [];
                $sub_r = [];
            }

            return $sub_r;
        } else {
            return $this->addError('no store ('.$ep_uri.')');
        }
    }

    public function getStore($ep_uri)
    {
        /* local store */
        if ((!$ep_uri || $ep_uri == ARC2::getScriptURI()) && ('local' == $this->v('sparqlscript_default_endpoint', '', $this->a))) {
            if (!isset($this->local_store)) {
                $this->local_store = ARC2::getStore($this->a);
            } /* @@todo error checking */

            return $this->local_store;
        } elseif ($ep_uri) {
            ARC2::inc('RemoteStore');
            $conf = array_merge($this->a, ['remote_store_endpoint' => $ep_uri, 'reader_timeout' => 10]);

            return new ARC2_RemoteStore($conf, $this);
        }

        return 0;
    }

    public function processAssignmentBlock($block)
    {
        $sub_type = $block['sub_type'];
        $m = 'process'.$this->camelCase($sub_type).'AssignmentBlock';
        if (!method_exists($this, $m)) {
            return $this->addError('Unknown method "'.$m.'"');
        }

        return $this->$m($block);
    }

    public function processQueryAssignmentBlock($block)
    {
        $qr = $this->processQueryBlock($block['query']);
        if ($this->getErrors() || !isset($qr['query_type'])) {
            return 0;
        }
        $qt = $qr['query_type'];
        $vts = ['ask' => 'bool', 'select' => 'rows', 'desribe' => 'doc', 'construct' => 'doc'];
        $r = [
            'value_type' => isset($vts[$qt]) ? $vts[$qt] : $qt.' result',
            'value' => ('select' == $qt) ? $this->v('rows', [], $qr['result']) : $qr['result'],
        ];
        $this->env['vars'][$block['var']['value']] = $r;
    }

    public function processStringAssignmentBlock($block)
    {
        $r = ['value_type' => 'literal', 'value' => $this->replacePlaceholders($block['string']['value'])];
        $this->env['vars'][$block['var']['value']] = $r;
    }

    public function processVarAssignmentBlock($block)
    {
        if (isset($this->env['vars'][$block['var2']['value']])) {
            $this->env['vars'][$block['var']['value']] = $this->env['vars'][$block['var2']['value']];
        } else {
            $this->env['vars'][$block['var']['value']] = ['value_type' => 'undefined', 'value' => ''];
        }
    }

    public function processPlaceholderAssignmentBlock($block)
    {
        $ph_val = $this->getPlaceholderValue($block['placeholder']['value']);
        $this->env['vars'][$block['var']['value']] = ['value_type' => 'undefined', 'value' => $ph_val];
    }

    public function processVarMergeAssignmentBlock($block)
    {
        $val1 = isset($this->env['vars'][$block['var2']['value']]) ? $this->env['vars'][$block['var2']['value']] : ['value_type' => 'undefined', 'value' => ''];
        $val2 = isset($this->env['vars'][$block['var3']['value']]) ? $this->env['vars'][$block['var3']['value']] : ['value_type' => 'undefined', 'value' => ''];
        if (is_array($val1) && is_array($val2)) {
            $this->env['vars'][$block['var']['value']] = ['value_type' => $val2['value_type'], 'value' => array_merge($val1['value'], $val2['value'])];
        } elseif (is_numeric($val1) && is_numeric($val2)) {
            $this->env['vars'][$block['var']['value']] = $val1 + $val2;
        }
    }

    public function processFunctionCallAssignmentBlock($block)
    {
        $sub_r = $this->processFunctionCallBlock($block['function_call']);
        if ($this->getErrors()) {
            return 0;
        }
        $this->env['vars'][$block['var']['value']] = $sub_r;
    }

    public function processReturnBlock($block)
    {
        $sub_type = $block['sub_type'];
        $m = 'process'.$this->camelCase($sub_type).'AssignmentBlock';
        if (!method_exists($this, $m)) {
            return $this->addError('Unknown method "'.$m.'"');
        }
        $sub_r = $this->$m($block);
        $this->return = 1;

        return $sub_r;
    }

    public function processIfblockBlock($block)
    {
        if ($this->testCondition($block['condition'])) {
            $blocks = $block['blocks'];
        } else {
            $blocks = $block['else_blocks'];
        }
        foreach ($blocks as $block) {
            $sub_r = $this->processBlock($block);
            if ($this->getErrors()) {
                return 0;
            }
        }
    }

    public function testCondition($cond)
    {
        $m = 'test'.$this->camelCase($cond['type']).'Condition';
        if (!method_exists($this, $m)) {
            return $this->addError('Unknown method "'.$m.'"');
        }

        return $this->$m($cond);
    }

    public function testVarCondition($cond)
    {
        $r = 0;
        $vn = $cond['value'];
        if (isset($this->env['vars'][$vn])) {
            $r = $this->env['vars'][$vn]['value'];
        }
        $op = $this->v('operator', '', $cond);
        if ('!' == $op) {
            $r = !$r;
        }

        return $r ? true : false;
    }

    public function testPlaceholderCondition($cond)
    {
        $val = $this->getPlaceholderValue($cond['value']);
        $r = $val ? true : false;
        $op = $this->v('operator', '', $cond);
        if ('!' == $op) {
            $r = !$r;
        }

        return $r;
    }

    public function testExpressionCondition($cond)
    {
        $m = 'test'.$this->camelCase($cond['sub_type']).'ExpressionCondition';
        if (!method_exists($this, $m)) {
            return $this->addError('Unknown method "'.$m.'"');
        }

        return $this->$m($cond);
    }

    public function testRelationalExpressionCondition($cond)
    {
        $op = $cond['operator'];
        if ('=' == $op) {
            $op = '==';
        }
        $val1 = $this->getPatternValue($cond['patterns'][0]);
        $val2 = $this->getPatternValue($cond['patterns'][1]);
        eval('$result = ($val1 '.$op.' $val2) ? 1 : 0;');

        return $result;
    }

    public function testAndExpressionCondition($cond)
    {
        foreach ($cond['patterns'] as $pattern) {
            if (!$this->testCondition($pattern)) {
                return false;
            }
        }

        return true;
    }

    public function getPatternValue($pattern)
    {
        $m = 'get'.$this->camelCase($pattern['type']).'PatternValue';
        if (!method_exists($this, $m)) {
            return '';
        }

        return $this->$m($pattern);
    }

    public function getLiteralPatternValue($pattern)
    {
        return $pattern['value'];
    }

    public function getPlaceholderPatternValue($pattern)
    {
        return $this->getPlaceholderValue($pattern['value']);
    }

    public function processForblockBlock($block)
    {
        $set = $this->v($block['set'], ['value' => []], $this->env['vars']);
        $entries = isset($set['value']) ? $set['value'] : $set;
        $iterator = $block['iterator'];
        $blocks = $block['blocks'];
        if (!is_array($entries)) {
            return 0;
        }
        $rc = count($entries);
        foreach ($entries as $i => $entry) {
            $val_type = $this->v('value_type', 'set', $set).' entry';
            $this->env['vars'][$iterator] = [
                'value' => $entry,
                'value_type' => $val_type,
                'meta' => [
                    'pos' => $i,
                    'odd_even' => ($i % 2) ? 'even' : 'odd',
                ],
            ];
            foreach ($blocks as $block) {
                $this->processBlock($block);
                if ($this->getErrors()) {
                    return 0;
                }
            }
        }
    }

    public function processLiteralBlock($block)
    {
        $this->env['output'] .= $this->replacePlaceholders($block['value'], 'output');
    }

    public function processFunctionCallBlock($block)
    {
        $uri = $this->replacePlaceholders($block['uri'], 'function_call');
        /* built-ins */
        if (str_starts_with($uri, $this->a['ns']['sps'])) {
            return $this->processBuiltinFunctionCallBlock($block);
        }
        /* remote functions */
    }

    public function processBuiltinFunctionCallBlock($block)
    {
        $fnc_uri = $this->replacePlaceholders($block['uri'], 'function_call');
        $fnc_name = substr($fnc_uri, strlen($this->a['ns']['sps']));
        if (preg_match('/^(get|post)$/i', $fnc_name, $m)) {
            return $this->processHTTPCall($block, strtoupper($m[1]));
        }
        if ('eval' == $fnc_name) {
            return $this->processEvalCall($block);
        }
    }

    public function processEvalCall($block)
    {
        if (!$block['args']) {
            return 0;
        }
        $arg = $block['args'][0];
        $script = '';
        if ('placeholder' == $arg['type']) {
            $script = $this->getPlaceholderValue($arg['value']);
        }
        if ('literal' == $arg['type']) {
            $script = $arg['value'];
        }
        if ('var' == $arg['type']) {
            $script = $this->getVarValue($arg['value']);
        }
        // echo "\n" . $script . $arg['type'];
        $this->processScript($script);
    }

    public function processHTTPCall($block, $mthd = 'GET')
    {
        ARC2::inc('Reader');
        $reader = new ARC2_Reader($this->a, $this);
        $url = $this->replacePlaceholders($block['args'][0]['value'], 'function_call');
        if ('GET' != $mthd) {
            $reader->setHTTPMethod($mthd);
            $reader->setCustomHeaders('Content-Type: application/x-www-form-urlencoded');
        }
        $to = $this->v('remote_call_timeout', 0, $this->a);
        $reader->activate($url, '', 0, $to);
        $format = $reader->getFormat();
        $resp = '';
        while ($d = $reader->readStream()) {
            $resp .= $d;
        }
        $reader->closeStream();
        unset($this->reader);

        return ['value_type' => 'http_response', 'value' => $resp];
    }

    public function extractVars($pattern, $input = '')
    {
        $vars = [];
        /* replace PHs, track ()s */
        $regex = $pattern;
        $vars = [];
        if (preg_match_all('/([\?\$]\{([^\}]+)\}|\([^\)]+\))/', $regex, $m)) {
            $matches = $m[1];
            $pre_vars = $m[2];
            foreach ($matches as $i => $match) {
                $vars[] = $pre_vars[$i];
                if ($pre_vars[$i]) {/* placeholder */
                    $regex = str_replace($match, '(.+)', $regex);
                } else {/* parentheses, but may contain placeholders */
                    $sub_regex = $match;
                    while (preg_match('/([\?\$]\{([^\}]+)\})/', $sub_regex, $m)) {
                        $sub_regex = str_replace($m[1], '(.+)', $sub_regex);
                        $vars[] = $m[2];
                    }
                    $regex = str_replace($match, $sub_regex, $regex);
                }
            }
            /* eval regex */
            if (@preg_match('/'.$regex.'/is', $input, $m)) {
                $vals = $m;
            } else {
                return 0;
            }
            for ($i = 0; $i < count($vars); ++$i) {
                if ($vars[$i]) {
                    $this->setVar($vars[$i], isset($vals[$i + 1]) ? $vals[$i + 1] : '');
                }
            }

            return 1;
        }

        /* no placeholders */
        return ($pattern == $input) ? 1 : 0;
    }
}
