<?php
/**
 * ARC2 RDF Parser (generic).
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 *
 * @version 2010-11-16
 */
ARC2::inc('Class');

class ARC2_RDFParser extends ARC2_Class
{
    /**
     * @var array<mixed>
     */
    public array $added_triples;

    public int $bnode_id;
    public string $bnode_prefix;

    public string $format;
    public int $keep_time_limit;
    public object $parser;
    public object $reader;
    public $skip_dupes;
    public int $t_count;

    /**
     * @var array<mixed>
     */
    public array $triples;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {/* proxy_host, proxy_port, proxy_skip, http_accept_header, http_user_agent_header, max_redirects, reader, skip_dupes */
        parent::__init();
        $this->a['format'] = $this->v('format', false, $this->a);
        $this->keep_time_limit = $this->v('keep_time_limit', 0, $this->a);
        $this->triples = [];
        $this->t_count = 0;
        $this->added_triples = [];
        $this->skip_dupes = $this->v('skip_dupes', false, $this->a);
        $this->bnode_prefix = $this->v('bnode_prefix', 'arc'.substr(md5(uniqid(rand())), 0, 4).'b', $this->a);
        $this->bnode_id = 0;
        $this->format = '';
    }

    public function setReader(&$reader)
    {
        $this->reader = $reader;
    }

    public function parse($path, $data = '')
    {
        /* reader */
        if (!isset($this->reader)) {
            ARC2::inc('Reader');
            $this->reader = new ARC2_Reader($this->a, $this);
        }
        $this->reader->activate($path, $data);
        /* format detection */
        $mappings = [
            'rdfxml' => 'RDFXML',
            'turtle' => 'Turtle',
            'sparqlxml' => 'SPOG',
            'ntriples' => 'Turtle',
            'html' => 'SemHTML',
            'rss' => 'RSS',
            'atom' => 'Atom',
            'sgajson' => 'SGAJSON',
            'cbjson' => 'CBJSON',
        ];
        $format = $this->reader->getFormat();
        if (!$format || !isset($mappings[$format])) {
            return $this->addError('No parser available for "'.$format.'".');
        }
        $this->format = $format;
        /* format parser */
        $suffix = $mappings[$format].'Parser';
        ARC2::inc($suffix);
        $cls = 'ARC2_'.$suffix;
        $this->parser = new $cls($this->a, $this);
        $this->parser->setReader($this->reader);

        return $this->parser->parse($path, $data);
    }

    public function parseData($data)
    {
        return $this->parse(ARC2::getScriptURI(), $data);
    }

    public function done()
    {
    }

    public function createBnodeID()
    {
        ++$this->bnode_id;

        return '_:'.$this->bnode_prefix.$this->bnode_id;
    }

    public function getTriples()
    {
        return $this->v('parser') ? $this->m('getTriples', false, [], $this->v('parser')) : [];
    }

    public function countTriples()
    {
        return $this->v('parser') ? $this->m('countTriples', false, 0, $this->v('parser')) : 0;
    }

    public function getSimpleIndex($flatten_objects = 1, $vals = '')
    {
        return ARC2::getSimpleIndex($this->getTriples(), $flatten_objects, $vals);
    }

    public function reset()
    {
        $this->__init();
        if (isset($this->reader)) {
            unset($this->reader);
        }
        if (isset($this->parser)) {
            $this->parser->__init();
            unset($this->parser);
        }
    }

    public function extractRDF($formats = '')
    {
        if (method_exists($this->parser, 'extractRDF')) {
            return $this->parser->extractRDF($formats);
        }
    }

    public function getEncoding($src = 'config')
    {
        if (method_exists($this->parser, 'getEncoding')) {
            return $this->parser->getEncoding($src);
        }
    }

    /**
     * returns the array of namespace prefixes encountered during parsing.
     *
     * @return array (keys = namespace URI / values = prefix used)
     */
    public function getParsedNamespacePrefixes()
    {
        if (isset($this->parser)) {
            return $this->v('nsp', [], $this->parser);
        }

        return $this->v('nsp', []);
    }
}
