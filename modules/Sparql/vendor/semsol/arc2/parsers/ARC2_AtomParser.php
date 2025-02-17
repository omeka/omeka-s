<?php
/*
@homepage <https://github.com/semsol/arc2>
@license W3C Software License and GPL

class:    ARC2 Atom Parser
author:   Benjamin Nowack
version:  2010-11-16
*/

ARC2::inc('LegacyXMLParser');

class ARC2_AtomParser extends ARC2_LegacyXMLParser
{
    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {/* reader */
        parent::__init();
        $this->triples = [];
        $this->target_encoding = '';
        $this->t_count = 0;
        $this->added_triples = [];
        $this->skip_dupes = false;
        $this->bnode_prefix = $this->v('bnode_prefix', 'arc'.substr(md5(uniqid(rand())), 0, 4).'b', $this->a);
        $this->bnode_id = 0;
        $this->cache = [];
        $this->allowCDataNodes = 0;
    }

    public function done()
    {
        $this->extractRDF();
    }

    public function setReader(&$reader)
    {
        $this->reader = $reader;
    }

    public function createBnodeID()
    {
        ++$this->bnode_id;

        return '_:'.$this->bnode_prefix.$this->bnode_id;
    }

    public function addT($t)
    {
        // if (!isset($t['o_datatype']))
        if ($this->skip_dupes) {
            // $h = md5(print_r($t, 1));
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

    public function getTriples()
    {
        return $this->v('triples', []);
    }

    public function countTriples()
    {
        return $this->t_count;
    }

    public function getSimpleIndex($flatten_objects = 1, $vals = '')
    {
        return ARC2::getSimpleIndex($this->getTriples(), $flatten_objects, $vals);
    }

    public function extractRDF()
    {
        $index = $this->getNodeIndex();
        // print_r($index);
        $this->rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $this->atom = 'http://www.w3.org/2005/Atom';
        $this->rss = 'http://purl.org/rss/1.0/';
        $this->dc = 'http://purl.org/dc/elements/1.1/';
        $this->sioc = 'http://rdfs.org/sioc/ns#';
        $this->dct = 'http://purl.org/dc/terms/';
        $this->content = 'http://purl.org/rss/1.0/modules/content/';
        $this->enc = 'http://purl.oclc.org/net/rss_2.0/enc#';
        $this->mappings = [
            'feed' => $this->rss.'channel',
            'entry' => $this->rss.'item',
            'title' => $this->rss.'title',
            'link' => $this->rss.'link',
            'summary' => $this->rss.'description',
            'content' => $this->content.'encoded',
            'id' => $this->dc.'identifier',
            'author' => $this->dc.'creator',
            'category' => $this->dc.'subject',
            'updated' => $this->dc.'date',
            'source' => $this->dc.'source',
        ];
        $this->dt_props = [
            $this->dc.'identifier',
            $this->rss.'link',
        ];
        foreach ($index as $p_id => $nodes) {
            foreach ($nodes as $pos => $node) {
                $tag = $this->v('tag', '', $node);
                if ('feed' == $tag) {
                    $struct = $this->extractChannel($index[$node['id']]);
                    $triples = ARC2::getTriplesFromIndex($struct);
                    foreach ($triples as $t) {
                        $this->addT($t);
                    }
                } elseif ('entry' == $tag) {
                    $struct = $this->extractItem($index[$node['id']]);
                    $triples = ARC2::getTriplesFromIndex($struct);
                    foreach ($triples as $t) {
                        $this->addT($t);
                    }
                }
            }
        }
    }

    public function extractChannel($els)
    {
        list($props, $sub_index) = $this->extractProps($els, 'channel');
        $uri = $props[$this->rss.'link'][0]['value'];

        return ARC2::getMergedIndex([$uri => $props], $sub_index);
    }

    public function extractItem($els)
    {
        list($props, $sub_index) = $this->extractProps($els, 'item');
        $uri = $props[$this->rss.'link'][0]['value'];

        return ARC2::getMergedIndex([$uri => $props], $sub_index);
    }

    public function extractProps($els, $container)
    {
        $r = [$this->rdf.'type' => [['value' => $this->rss.$container, 'type' => 'uri']]];
        $sub_index = [];
        foreach ($els as $info) {
            /* key */
            $tag = $info['tag'];
            if (!preg_match('/^[a-z0-9]+\:/i', $tag)) {
                $k = isset($this->mappings[$tag]) ? $this->mappings[$tag] : '';
            } elseif (isset($this->mappings[$tag])) {
                $k = $this->mappings[$tag];
            } else {/* qname */
                $k = $this->expandPName($tag);
            }
            // echo $k . "\n";
            if (('channel' == $container) && ($k == $this->rss.'item')) {
                continue;
            }
            /* val */
            $v = trim($info['cdata']);
            if (!$v) {
                $v = $this->v('href uri', '', $info['a']);
            }
            /* prop */
            if ($k) {
                /* content handling */
                if (in_array($k, [$this->rss.'description', $this->content.'encoded'])) {
                    $v = $this->getNodeContent($info);
                }
                /* source handling */
                elseif ($k == $this->dc.'source') {
                    $sub_nodes = $this->node_index[$info['id']];
                    foreach ($sub_nodes as $sub_pos => $sub_info) {
                        if ('id' == $sub_info['tag']) {
                            $v = trim($sub_info['cdata']);
                        }
                    }
                }
                /* link handling */
                elseif ($k == $this->rss.'link') {
                    if ($link_type = $this->v('type', '', $info['a'])) {
                        $k2 = $this->dc.'format';
                        if (!isset($sub_index[$v])) {
                            $sub_index[$v] = [];
                        }
                        if (!isset($sub_index[$v][$k2])) {
                            $sub_index[$v][$k2] = [];
                        }
                        $sub_index[$v][$k2][] = ['value' => $link_type, 'type' => 'literal'];
                    }
                }
                /* author handling */
                elseif ($k == $this->dc.'creator') {
                    $sub_nodes = $this->node_index[$info['id']];
                    foreach ($sub_nodes as $sub_pos => $sub_info) {
                        if ('name' == $sub_info['tag']) {
                            $v = trim($sub_info['cdata']);
                        }
                        if ('uri' == $sub_info['tag']) {
                            $k2 = $this->sioc.'has_creator';
                            $v2 = trim($sub_info['cdata']);
                            if (!isset($r[$k2])) {
                                $r[$k2] = [];
                            }
                            $r[$k2][] = ['value' => $v2, 'type' => 'uri'];
                        }
                    }
                }
                /* date handling */
                elseif (in_array($k, [$this->dc.'date', $this->dct.'modified'])) {
                    if (!preg_match('/^[0-9]{4}/', $v) && ($sub_v = strtotime($v)) && (-1 != $sub_v)) {
                        $tz = date('Z', $sub_v); /* timezone offset */
                        $sub_v -= $tz; /* utc */
                        $v = date('Y-m-d\TH:i:s\Z', $sub_v);
                    }
                }
                /* tag handling */
                elseif ($k == $this->dc.'subject') {
                    $v = $this->v('term', '', $info['a']);
                }
                /* other attributes in closed tags */
                elseif (!$v && ('closed' == $info['state']) && $info['a']) {
                    foreach ($info['a'] as $sub_k => $sub_v) {
                        if (!preg_match('/(xmlns|\:|type)/', $sub_k)) {
                            $v = $sub_v;
                            break;
                        }
                    }
                }
                if (!isset($r[$k])) {
                    $r[$k] = [];
                }
                $r[$k][] = ['value' => $v, 'type' => in_array($k, $this->dt_props) || !preg_match('/^[a-z0-9]+\:[^\s]+$/is', $v) ? 'literal' : 'uri'];
            }
        }

        return [$r, $sub_index];
    }

    public function initXMLParser()
    {
        if (!isset($this->xml_parser)) {
            $enc = preg_match('/^(utf\-8|iso\-8859\-1|us\-ascii)$/i', $this->getEncoding(), $m) ? $m[1] : 'UTF-8';
            $parser = xml_parser_create($enc);
            xml_parser_set_option($parser, \XML_OPTION_SKIP_WHITE, 0);
            xml_parser_set_option($parser, \XML_OPTION_CASE_FOLDING, 0);
            xml_set_element_handler($parser, [$this, 'open'], [$this, 'close']);
            xml_set_character_data_handler($parser, [$this, 'cData']);
            xml_set_start_namespace_decl_handler($parser, [$this, 'nsDecl']);
            $this->xml_parser = $parser;
        }
    }
}
