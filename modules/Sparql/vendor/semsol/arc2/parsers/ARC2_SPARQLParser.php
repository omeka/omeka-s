<?php
/**
 * ARC2 SPARQL Parser.
 *
 * @author Benjamin Nowack
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 *
 * @version 2010-11-16
 */
ARC2::inc('TurtleParser');

class ARC2_SPARQLParser extends ARC2_TurtleParser
{
    /**
     * @var array<mixed>
     */
    public array $bnode_pattern_index;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->bnode_prefix = $this->v('bnode_prefix', 'arc'.substr(md5(uniqid(rand())), 0, 4).'b', $this->a);
        $this->bnode_id = 0;
        $this->bnode_pattern_index = ['patterns' => [], 'bnodes' => []];
    }

    public function parse($q, $src = '', $iso_fallback = 'ignore')
    {
        $this->setDefaultPrefixes();
        $this->base = $src ? $this->calcBase($src) : ARC2::getRequestURI();
        $this->r = [
            'base' => '',
            'vars' => [],
            'prefixes' => [],
        ];
        $this->unparsed_code = $q;
        list($r, $v) = $this->xQuery($q);
        if ($r) {
            $this->r['query'] = $r;
            $this->unparsed_code = trim($v);
        } elseif (!$this->getErrors() && !$this->unparsed_code) {
            $this->addError('Query not properly closed');
        }
        $this->r['prefixes'] = $this->prefixes;
        $this->r['base'] = $this->base;
        /* remove trailing comments */
        while (preg_match('/^\s*(\#[^\xd\xa]*)(.*)$/si', $this->unparsed_code, $m)) {
            $this->unparsed_code = $m[2];
        }
        if ($this->unparsed_code && !$this->getErrors()) {
            $rest = preg_replace('/[\x0a|\x0d]/i', ' ', substr($this->unparsed_code, 0, 30));
            $msg = trim($rest) ? 'Could not properly handle "'.$rest.'"' : 'Syntax error, probably an incomplete pattern';
            $this->addError($msg);
        }
    }

    public function getQueryInfos()
    {
        return $this->v('r', []);
    }

    /* 1 */

    public function xQuery($v)
    {
        list($r, $v) = $this->xPrologue($v);
        foreach (['Select', 'Construct', 'Describe', 'Ask'] as $type) {
            $m = 'x'.$type.'Query';
            if ((list($r, $v) = $this->$m($v)) && $r) {
                return [$r, $v];
            }
        }

        return [0, $v];
    }

    /* 2 */

    public function xPrologue($v)
    {
        $r = 0;
        if ((list($sub_r, $v) = $this->xBaseDecl($v)) && $sub_r) {
            $this->base = $sub_r;
            $r = 1;
        }
        while ((list($sub_r, $v) = $this->xPrefixDecl($v)) && $sub_r) {
            $this->prefixes[$sub_r['prefix']] = $sub_r['uri'];
            $r = 1;
        }

        return [$r, $v];
    }

    /* 5.. */

    public function xSelectQuery($v)
    {
        if ($sub_r = $this->x('SELECT\s+', $v)) {
            $r = [
                'type' => 'select',
                'result_vars' => [],
                'dataset' => [],
            ];
            $all_vars = 0;
            $sub_v = $sub_r[1];
            /* distinct, reduced */
            if ($sub_r = $this->x('(DISTINCT|REDUCED)\s+', $sub_v)) {
                $r[strtolower($sub_r[1])] = 1;
                $sub_v = $sub_r[2];
            }
            /* result vars */
            if ($sub_r = $this->x('\*\s+', $sub_v)) {
                $all_vars = 1;
                $sub_v = $sub_r[1];
            } else {
                while ((list($sub_r, $sub_v) = $this->xResultVar($sub_v)) && $sub_r) {
                    $r['result_vars'][] = $sub_r;
                }
            }
            if (!$all_vars && !count($r['result_vars'])) {
                $this->addError('No result bindings specified.');
            }
            /* dataset */
            while ((list($sub_r, $sub_v) = $this->xDatasetClause($sub_v)) && $sub_r) {
                $r['dataset'][] = $sub_r;
            }
            /* where */
            if ((list($sub_r, $sub_v) = $this->xWhereClause($sub_v)) && $sub_r) {
                $r['pattern'] = $sub_r;
            } else {
                return [0, $v];
            }
            /* solution modifier */
            if ((list($sub_r, $sub_v) = $this->xSolutionModifier($sub_v)) && $sub_r) {
                $r = array_merge($r, $sub_r);
            }
            /* all vars */
            if ($all_vars) {
                foreach ($this->r['vars'] as $var) {
                    $r['result_vars'][] = ['var' => $var, 'aggregate' => 0, 'alias' => ''];
                }
                if (!$r['result_vars']) {
                    $r['result_vars'][] = '*';
                }
            }

            return [$r, $sub_v];
        }

        return [0, $v];
    }

    public function xResultVar($v)
    {
        return $this->xVar($v);
    }

    /* 6.. */

    public function xConstructQuery($v)
    {
        if ($sub_r = $this->x('CONSTRUCT\s*', $v)) {
            $r = [
                'type' => 'construct',
                'dataset' => [],
            ];
            $sub_v = $sub_r[1];
            /* construct template */
            if ((list($sub_r, $sub_v) = $this->xConstructTemplate($sub_v)) && is_array($sub_r)) {
                $r['construct_triples'] = $sub_r;
            } else {
                $this->addError('Construct Template not found');

                return [0, $v];
            }
            /* dataset */
            while ((list($sub_r, $sub_v) = $this->xDatasetClause($sub_v)) && $sub_r) {
                $r['dataset'][] = $sub_r;
            }
            /* where */
            if ((list($sub_r, $sub_v) = $this->xWhereClause($sub_v)) && $sub_r) {
                $r['pattern'] = $sub_r;
            } else {
                return [0, $v];
            }
            /* solution modifier */
            if ((list($sub_r, $sub_v) = $this->xSolutionModifier($sub_v)) && $sub_r) {
                $r = array_merge($r, $sub_r);
            }

            return [$r, $sub_v];
        }

        return [0, $v];
    }

    /* 7.. */

    public function xDescribeQuery($v)
    {
        if ($sub_r = $this->x('DESCRIBE\s+', $v)) {
            $r = [
                'type' => 'describe',
                'result_vars' => [],
                'result_uris' => [],
                'dataset' => [],
            ];
            $sub_v = $sub_r[1];
            $all_vars = 0;
            /* result vars/uris */
            if ($sub_r = $this->x('\*\s+', $sub_v)) {
                $all_vars = 1;
                $sub_v = $sub_r[1];
            } else {
                do {
                    $proceed = 0;
                    if ((list($sub_r, $sub_v) = $this->xResultVar($sub_v)) && $sub_r) {
                        $r['result_vars'][] = $sub_r;
                        $proceed = 1;
                    }
                    if ((list($sub_r, $sub_v) = $this->xIRIref($sub_v)) && $sub_r) {
                        $r['result_uris'][] = $sub_r;
                        $proceed = 1;
                    }
                } while ($proceed);
            }
            if (!$all_vars && !count($r['result_vars']) && !count($r['result_uris'])) {
                $this->addError('No result bindings specified.');
            }
            /* dataset */
            while ((list($sub_r, $sub_v) = $this->xDatasetClause($sub_v)) && $sub_r) {
                $r['dataset'][] = $sub_r;
            }
            /* where */
            if ((list($sub_r, $sub_v) = $this->xWhereClause($sub_v)) && $sub_r) {
                $r['pattern'] = $sub_r;
            }
            /* solution modifier */
            if ((list($sub_r, $sub_v) = $this->xSolutionModifier($sub_v)) && $sub_r) {
                $r = array_merge($r, $sub_r);
            }
            /* all vars */
            if ($all_vars) {
                foreach ($this->r['vars'] as $var) {
                    $r['result_vars'][] = ['var' => $var, 'aggregate' => 0, 'alias' => ''];
                }
            }

            return [$r, $sub_v];
        }

        return [0, $v];
    }

    /* 8.. */

    public function xAskQuery($v)
    {
        if ($sub_r = $this->x('ASK\s+', $v)) {
            $r = [
                'type' => 'ask',
                'dataset' => [],
            ];
            $sub_v = $sub_r[1];
            /* dataset */
            while ((list($sub_r, $sub_v) = $this->xDatasetClause($sub_v)) && $sub_r) {
                $r['dataset'][] = $sub_r;
            }
            /* where */
            if ((list($sub_r, $sub_v) = $this->xWhereClause($sub_v)) && $sub_r) {
                $r['pattern'] = $sub_r;

                return [$r, $sub_v];
            } else {
                $this->addError('Missing or invalid WHERE clause.');
            }
        }

        return [0, $v];
    }

    /* 9, 10, 11, 12 */

    public function xDatasetClause($v)
    {
        if ($r = $this->x('FROM(\s+NAMED)?\s+', $v)) {
            $named = $r[1] ? 1 : 0;
            if ((list($r, $sub_v) = $this->xIRIref($r[2])) && $r) {
                return [['graph' => $r, 'named' => $named], $sub_v];
            }
        }

        return [0, $v];
    }

    /* 13 */

    public function xWhereClause($v)
    {
        if ($r = $this->x('(WHERE)?', $v)) {
            $v = $r[2];
        }
        if ((list($r, $v) = $this->xGroupGraphPattern($v)) && $r) {
            return [$r, $v];
        }

        return [0, $v];
    }

    /* 14, 15 */

    public function xSolutionModifier($v)
    {
        $r = [];
        if ((list($sub_r, $sub_v) = $this->xOrderClause($v)) && $sub_r) {
            $r['order_infos'] = $sub_r;
        }
        while ((list($sub_r, $sub_v) = $this->xLimitOrOffsetClause($sub_v)) && $sub_r) {
            $r = array_merge($r, $sub_r);
        }

        return ($v == $sub_v) ? [0, $v] : [$r, $sub_v];
    }

    /* 18, 19 */

    public function xLimitOrOffsetClause($v)
    {
        if ($sub_r = $this->x('(LIMIT|OFFSET)', $v)) {
            $key = strtolower($sub_r[1]);
            $sub_v = $sub_r[2];
            if ((list($sub_r, $sub_v) = $this->xINTEGER($sub_v)) && (false !== $sub_r)) {
                return [[$key => $sub_r], $sub_v];
            }
            if ((list($sub_r, $sub_v) = $this->xPlaceholder($sub_v)) && (false !== $sub_r)) {
                return [[$key => $sub_r], $sub_v];
            }
        }

        return [0, $v];
    }

    /* 16 */

    public function xOrderClause($v)
    {
        if ($sub_r = $this->x('ORDER BY\s+', $v)) {
            $sub_v = $sub_r[1];
            $r = [];
            while ((list($sub_r, $sub_v) = $this->xOrderCondition($sub_v)) && $sub_r) {
                $r[] = $sub_r;
            }
            if (count($r)) {
                return [$r, $sub_v];
            } else {
                $this->addError('No order conditions specified.');
            }
        }

        return [0, $v];
    }

    /* 17, 27 */

    public function xOrderCondition($v)
    {
        if ($sub_r = $this->x('(ASC|DESC)', $v)) {
            $dir = strtolower($sub_r[1]);
            $sub_v = $sub_r[2];
            if ((list($sub_r, $sub_v) = $this->xBrackettedExpression($sub_v)) && $sub_r) {
                $sub_r['direction'] = $dir;

                return [$sub_r, $sub_v];
            }
        } elseif ((list($sub_r, $sub_v) = $this->xVar($v)) && $sub_r) {
            $sub_r['direction'] = 'asc';

            return [$sub_r, $sub_v];
        } elseif ((list($sub_r, $sub_v) = $this->xBrackettedExpression($v)) && $sub_r) {
            return [$sub_r, $sub_v];
        } elseif ((list($sub_r, $sub_v) = $this->xBuiltInCall($v)) && $sub_r) {
            $sub_r['direction'] = 'asc';

            return [$sub_r, $sub_v];
        } elseif ((list($sub_r, $sub_v) = $this->xFunctionCall($v)) && $sub_r) {
            $sub_r['direction'] = 'asc';

            return [$sub_r, $sub_v];
        }

        return [0, $v];
    }

    /* 20 */

    public function xGroupGraphPattern($v)
    {
        $pattern_id = substr(md5(uniqid(rand())), 0, 4);
        if ($sub_r = $this->x('\{', $v)) {
            $r = ['type' => 'group', 'patterns' => []];
            $sub_v = $sub_r[1];
            if ((list($sub_r, $sub_v) = $this->xTriplesBlock($sub_v)) && $sub_r) {
                $this->indexBnodes($sub_r, $pattern_id);
                $r['patterns'][] = ['type' => 'triples', 'patterns' => $sub_r];
            }
            do {
                $proceed = 0;
                if ((list($sub_r, $sub_v) = $this->xGraphPatternNotTriples($sub_v)) && $sub_r) {
                    $r['patterns'][] = $sub_r;
                    $pattern_id = substr(md5(uniqid(rand())), 0, 4);
                    $proceed = 1;
                } elseif ((list($sub_r, $sub_v) = $this->xFilter($sub_v)) && $sub_r) {
                    $r['patterns'][] = ['type' => 'filter', 'constraint' => $sub_r];
                    $proceed = 1;
                }
                if ($sub_r = $this->x('\.', $sub_v)) {
                    $sub_v = $sub_r[1];
                }
                if ((list($sub_r, $sub_v) = $this->xTriplesBlock($sub_v)) && $sub_r) {
                    $this->indexBnodes($sub_r, $pattern_id);
                    $r['patterns'][] = ['type' => 'triples', 'patterns' => $sub_r];
                    $proceed = 1;
                }
                if ((list($sub_r, $sub_v) = $this->xPlaceholder($sub_v)) && $sub_r) {
                    $r['patterns'][] = $sub_r;
                    $proceed = 1;
                }
            } while ($proceed);
            if ($sub_r = $this->x('\}', $sub_v)) {
                $sub_v = $sub_r[1];

                return [$r, $sub_v];
            }
            $rest = preg_replace('/[\x0a|\x0d]/i', ' ', substr($sub_v, 0, 30));
            $this->addError('Incomplete or invalid Group Graph pattern. Could not handle "'.$rest.'"');
        }

        return [0, $v];
    }

    public function indexBnodes($triples, $pattern_id)
    {
        $index_id = count($this->bnode_pattern_index['patterns']);
        $index_id = $pattern_id;
        $this->bnode_pattern_index['patterns'][] = $triples;
        foreach ($triples as $t) {
            foreach (['s', 'p', 'o'] as $term) {
                if ('bnode' == $t[$term.'_type']) {
                    $val = $t[$term];
                    if (isset($this->bnode_pattern_index['bnodes'][$val]) && ($this->bnode_pattern_index['bnodes'][$val] != $index_id)) {
                        $this->addError('Re-used bnode label "'.$val.'" across graph patterns');
                    } else {
                        $this->bnode_pattern_index['bnodes'][$val] = $index_id;
                    }
                }
            }
        }
    }

    /* 22.., 25.. */

    public function xGraphPatternNotTriples($v)
    {
        if ((list($sub_r, $sub_v) = $this->xOptionalGraphPattern($v)) && $sub_r) {
            return [$sub_r, $sub_v];
        }
        if ((list($sub_r, $sub_v) = $this->xGraphGraphPattern($v)) && $sub_r) {
            return [$sub_r, $sub_v];
        }
        $r = ['type' => 'union', 'patterns' => []];
        $sub_v = $v;
        do {
            $proceed = 0;
            if ((list($sub_r, $sub_v) = $this->xGroupGraphPattern($sub_v)) && $sub_r) {
                $r['patterns'][] = $sub_r;
                if ($sub_r = $this->x('UNION', $sub_v)) {
                    $sub_v = $sub_r[1];
                    $proceed = 1;
                }
            }
        } while ($proceed);
        $pc = count($r['patterns']);
        if (1 == $pc) {
            return [$r['patterns'][0], $sub_v];
        } elseif ($pc > 1) {
            return [$r, $sub_v];
        }

        return [0, $v];
    }

    /* 23 */

    public function xOptionalGraphPattern($v)
    {
        if ($sub_r = $this->x('OPTIONAL', $v)) {
            $sub_v = $sub_r[1];
            if ((list($sub_r, $sub_v) = $this->xGroupGraphPattern($sub_v)) && $sub_r) {
                return [['type' => 'optional', 'patterns' => $sub_r['patterns']], $sub_v];
            }
            $this->addError('Missing or invalid Group Graph Pattern after OPTIONAL');
        }

        return [0, $v];
    }

    /* 24.. */

    public function xGraphGraphPattern($v)
    {
        if ($sub_r = $this->x('GRAPH', $v)) {
            $sub_v = $sub_r[1];
            $r = ['type' => 'graph', 'var' => '', 'uri' => '', 'patterns' => []];
            if ((list($sub_r, $sub_v) = $this->xVar($sub_v)) && $sub_r) {
                $r['var'] = $sub_r;
            } elseif ((list($sub_r, $sub_v) = $this->xIRIref($sub_v)) && $sub_r) {
                $r['uri'] = $sub_r;
            }
            if ($r['var'] || $r['uri']) {
                if ((list($sub_r, $sub_v) = $this->xGroupGraphPattern($sub_v)) && $sub_r) {
                    $r['patterns'][] = $sub_r;

                    return [$r, $sub_v];
                }
                $this->addError('Missing or invalid Graph Pattern');
            }
        }

        return [0, $v];
    }

    /* 26.., 27.. */

    public function xFilter($v)
    {
        if ($r = $this->x('FILTER', $v)) {
            $sub_v = $r[1];
            if ((list($r, $sub_v) = $this->xBrackettedExpression($sub_v)) && $r) {
                return [$r, $sub_v];
            }
            if ((list($r, $sub_v) = $this->xBuiltInCall($sub_v)) && $r) {
                return [$r, $sub_v];
            }
            if ((list($r, $sub_v) = $this->xFunctionCall($sub_v)) && $r) {
                return [$r, $sub_v];
            }
            $this->addError('Incomplete FILTER');
        }

        return [0, $v];
    }

    /* 28.. */

    public function xFunctionCall($v)
    {
        if ((list($r, $sub_v) = $this->xIRIref($v)) && $r) {
            if ((list($sub_r, $sub_v) = $this->xArgList($sub_v)) && $sub_r) {
                return [['type' => 'function_call', 'uri' => $r, 'args' => $sub_r], $sub_v];
            }
        }

        return [0, $v];
    }

    /* 29 */

    public function xArgList($v)
    {
        $r = [];
        $sub_v = $v;
        $closed = 0;
        if ($sub_r = $this->x('\(', $sub_v)) {
            $sub_v = $sub_r[1];
            do {
                $proceed = 0;
                if ((list($sub_r, $sub_v) = $this->xExpression($sub_v)) && $sub_r) {
                    $r[] = $sub_r;
                    if ($sub_r = $this->x('\,', $sub_v)) {
                        $sub_v = $sub_r[1];
                        $proceed = 1;
                    }
                }
                if ($sub_r = $this->x('\)', $sub_v)) {
                    $sub_v = $sub_r[1];
                    $closed = 1;
                    $proceed = 0;
                }
            } while ($proceed);
        }

        return $closed ? [$r, $sub_v] : [0, $v];
    }

    /* 30, 31 */

    public function xConstructTemplate($v)
    {
        if ($sub_r = $this->x('\{', $v)) {
            $r = [];
            if ((list($sub_r, $sub_v) = $this->xTriplesBlock($sub_r[1])) && is_array($sub_r)) {
                $r = $sub_r;
            }
            if ($sub_r = $this->x('\}', $sub_v)) {
                return [$r, $sub_r[1]];
            }
        }

        return [0, $v];
    }

    /* 46, 47 */

    public function xExpression($v)
    {
        if ((list($sub_r, $sub_v) = $this->xConditionalAndExpression($v)) && $sub_r) {
            $r = ['type' => 'expression', 'sub_type' => 'or', 'patterns' => [$sub_r]];
            do {
                $proceed = 0;
                if ($sub_r = $this->x('\|\|', $sub_v)) {
                    $sub_v = $sub_r[1];
                    if ((list($sub_r, $sub_v) = $this->xConditionalAndExpression($sub_v)) && $sub_r) {
                        $r['patterns'][] = $sub_r;
                        $proceed = 1;
                    }
                }
            } while ($proceed);

            return 1 == count($r['patterns']) ? [$r['patterns'][0], $sub_v] : [$r, $sub_v];
        }

        return [0, $v];
    }

    /* 48.., 49.. */

    public function xConditionalAndExpression($v)
    {
        if ((list($sub_r, $sub_v) = $this->xRelationalExpression($v)) && $sub_r) {
            $r = ['type' => 'expression', 'sub_type' => 'and', 'patterns' => [$sub_r]];
            do {
                $proceed = 0;
                if ($sub_r = $this->x('\&\&', $sub_v)) {
                    $sub_v = $sub_r[1];
                    if ((list($sub_r, $sub_v) = $this->xRelationalExpression($sub_v)) && $sub_r) {
                        $r['patterns'][] = $sub_r;
                        $proceed = 1;
                    }
                }
            } while ($proceed);

            return 1 == count($r['patterns']) ? [$r['patterns'][0], $sub_v] : [$r, $sub_v];
        }

        return [0, $v];
    }

    /* 50, 51 */

    public function xRelationalExpression($v)
    {
        if ((list($sub_r, $sub_v) = $this->xAdditiveExpression($v)) && $sub_r) {
            $r = ['type' => 'expression', 'sub_type' => 'relational', 'patterns' => [$sub_r]];
            do {
                $proceed = 0;
                /* don't mistake '<' + uriref with '<'-operator ("longest token" rule) */
                if ((list($sub_r, $sub_v) = $this->xIRI_REF($sub_v)) && $sub_r) {
                    $this->addError('Expected operator, found IRIref: "'.$sub_r.'".');
                }
                if ($sub_r = $this->x('(\!\=|\=\=|\=|\<\=|\>\=|\<|\>)', $sub_v)) {
                    $op = $sub_r[1];
                    $sub_v = $sub_r[2];
                    $r['operator'] = $op;
                    if ((list($sub_r, $sub_v) = $this->xAdditiveExpression($sub_v)) && $sub_r) {
                        // $sub_r['operator'] = $op;
                        $r['patterns'][] = $sub_r;
                        $proceed = 1;
                    }
                }
            } while ($proceed);

            return 1 == count($r['patterns']) ? [$r['patterns'][0], $sub_v] : [$r, $sub_v];
        }

        return [0, $v];
    }

    /* 52 */

    public function xAdditiveExpression($v)
    {
        if ((list($sub_r, $sub_v) = $this->xMultiplicativeExpression($v)) && $sub_r) {
            $r = ['type' => 'expression', 'sub_type' => 'additive', 'patterns' => [$sub_r]];
            do {
                $proceed = 0;
                if ($sub_r = $this->x('(\+|\-)', $sub_v)) {
                    $op = $sub_r[1];
                    $sub_v = $sub_r[2];
                    if ((list($sub_r, $sub_v) = $this->xMultiplicativeExpression($sub_v)) && $sub_r) {
                        $sub_r['operator'] = $op;
                        $r['patterns'][] = $sub_r;
                        $proceed = 1;
                    } elseif ((list($sub_r, $sub_v) = $this->xNumericLiteral($sub_v)) && $sub_r) {
                        $r['patterns'][] = ['type' => 'numeric', 'operator' => $op, 'value' => $sub_r];
                        $proceed = 1;
                    }
                }
            } while ($proceed);

            // return array($r, $sub_v);
            return 1 == count($r['patterns']) ? [$r['patterns'][0], $sub_v] : [$r, $sub_v];
        }

        return [0, $v];
    }

    /* 53 */

    public function xMultiplicativeExpression($v)
    {
        if ((list($sub_r, $sub_v) = $this->xUnaryExpression($v)) && $sub_r) {
            $r = ['type' => 'expression', 'sub_type' => 'multiplicative', 'patterns' => [$sub_r]];
            do {
                $proceed = 0;
                if ($sub_r = $this->x('(\*|\/)', $sub_v)) {
                    $op = $sub_r[1];
                    $sub_v = $sub_r[2];
                    if ((list($sub_r, $sub_v) = $this->xUnaryExpression($sub_v)) && $sub_r) {
                        $sub_r['operator'] = $op;
                        $r['patterns'][] = $sub_r;
                        $proceed = 1;
                    }
                }
            } while ($proceed);

            return 1 == count($r['patterns']) ? [$r['patterns'][0], $sub_v] : [$r, $sub_v];
        }

        return [0, $v];
    }

    /* 54 */

    public function xUnaryExpression($v)
    {
        $sub_v = $v;
        $op = '';
        if ($sub_r = $this->x('(\!|\+|\-)', $sub_v)) {
            $op = $sub_r[1];
            $sub_v = $sub_r[2];
        }
        if ((list($sub_r, $sub_v) = $this->xPrimaryExpression($sub_v)) && $sub_r) {
            if (!is_array($sub_r)) {
                $sub_r = ['type' => 'unary', 'expression' => $sub_r];
            } elseif ($sub_op = $this->v1('operator', '', $sub_r)) {
                $ops = ['!!' => '', '++' => '+', '--' => '+', '+-' => '-', '-+' => '-'];
                $op = isset($ops[$op.$sub_op]) ? $ops[$op.$sub_op] : $op.$sub_op;
            }
            $sub_r['operator'] = $op;

            return [$sub_r, $sub_v];
        }

        return [0, $v];
    }

    /* 55 */

    public function xPrimaryExpression($v)
    {
        foreach (['BrackettedExpression', 'BuiltInCall', 'IRIrefOrFunction', 'RDFLiteral', 'NumericLiteral', 'BooleanLiteral', 'Var', 'Placeholder'] as $type) {
            $m = 'x'.$type;
            if ((list($sub_r, $sub_v) = $this->$m($v)) && $sub_r) {
                return [$sub_r, $sub_v];
            }
        }

        return [0, $v];
    }

    /* 56 */

    public function xBrackettedExpression($v)
    {
        if ($r = $this->x('\(', $v)) {
            if ((list($r, $sub_v) = $this->xExpression($r[1])) && $r) {
                if ($sub_r = $this->x('\)', $sub_v)) {
                    return [$r, $sub_r[1]];
                }
            }
        }

        return [0, $v];
    }

    /* 57.., 58.. */

    public function xBuiltInCall($v)
    {
        if ($sub_r = $this->x('(str|lang|langmatches|datatype|bound|sameterm|isiri|isuri|isblank|isliteral|regex)\s*\(', $v)) {
            $r = ['type' => 'built_in_call', 'call' => strtolower($sub_r[1])];
            if ((list($sub_r, $sub_v) = $this->xArgList('('.$sub_r[2])) && is_array($sub_r)) {
                $r['args'] = $sub_r;

                return [$r, $sub_v];
            }
        }

        return [0, $v];
    }

    /* 59.. */

    public function xIRIrefOrFunction($v)
    {
        if ((list($r, $v) = $this->xIRIref($v)) && $r) {
            if ((list($sub_r, $sub_v) = $this->xArgList($v)) && is_array($sub_r)) {
                return [['type' => 'function', 'uri' => $r, 'args' => $sub_r], $sub_v];
            }

            return [['type' => 'uri', 'uri' => $r], $sub_v];
        }
    }

    /* 70.. @@sync with TurtleParser */

    public function xIRI_REF($v)
    {
        if (($r = $this->x('\<(\$\{[^\>]*\})\>', $v)) && ($sub_r = $this->xPlaceholder($r[1]))) {
            return [$r[1], $r[2]];
        } elseif ($r = $this->x('\<([^\<\>\s\"\|\^`]*)\>', $v)) {
            return [$r[1] ? $r[1] : true, $r[2]];
        }
        /* allow reserved chars in obvious IRIs */
        elseif ($r = $this->x('\<(https?\:[^\s][^\<\>]*)\>', $v)) {
            return [$r[1] ? $r[1] : true, $r[2]];
        }

        return [0, $v];
    }
}
