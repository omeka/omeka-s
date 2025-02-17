<?php
/**
 * ARC2 MicroRDF Serializer.
 *
 * @author Benjamin Nowack
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 *
 * @version 2010-11-16
 */
ARC2::inc('RDFSerializer');

class ARC2_MicroRDFSerializer extends ARC2_RDFSerializer
{
    public string $content_header;
    public $label_store;

    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->content_header = 'text/html';
        $this->label_store = $this->v('label_store', '', $this->a);
    }

    public function getLabel($res, $ps = '')
    {
        if (!$ps) {
            $ps = [];
        }
        foreach ($ps as $p => $os) {
            if (preg_match('/[\/\#](name|label|summary|title|fn)$/i', $p)) {
                return $os[0]['value'];
            }
        }
        if (preg_match('/^\_\:/', $res)) {
            return 'An unnamed resource';
        }

        return $this->extractTermLabel($res);

        return preg_replace("/^(.*[\/\#])([^\/\#]+)$/", '\\2', str_replace('_', ' ', $res));
    }

    public function getSerializedIndex($index, $res = '')
    {
        $r = '';
        $n = "\n";
        if ($res) {
            $index = [$res => $index[$res]];
        }
        // return Trice::dump($index);
        $types = $this->v($this->expandPName('rdf:type'), [], $index);
        $main_type = $types ? $types[0]['value'] : '';
        foreach ($index as $s => $ps) {
            /* node */
            $r .= '
        <div class="rdf-item" '.$this->mdAttrs($s, $main_type).'>
          <h3 class="rdf-itemlabel"><a href="'.$s.'">'.ucfirst($this->getLabel($s, $ps)).'</a></h3>
      ';
            /* arcs */
            foreach ($ps as $p => $os) {
                $p_cls = strtolower($this->getPName($p));
                $p_cls = str_replace(':', '-', $p_cls);
                $r .= '
          <div class="rdf-prop '.$p_cls.'">
            <a class="rdf-proplabel" href="'.$p.'">'.ucfirst($this->getLabel($p)).':</a>
            <ul class="rdf-values">
        ';
                $oc = count($os);
                foreach ($os as $i => $o) {
                    $val = $this->getObjectValue($o, $p);
                    $cls = '';
                    if (0 == $i) {
                        $cls .= ($cls ? ' ' : '').'first';
                    }
                    if ($i == $oc - 1) {
                        $cls .= ($cls ? ' ' : '').'last';
                    }
                    $r .= $n.'<li'.($cls ? ' class="'.$cls.'"' : '').'>'.$val.'</li>';
                }
                $r .= '
            </ul>
            <div class="clb"></div>
          </div>
        ';
            }
            /* /node */
            $r .= '
        <div class="clb"></div>
        </div>
      ';
        }

        return $r;
    }

    public function getObjectValue($o, $p)
    {
        if ('uri' == $o['type']) {
            if (preg_match('/(jpe?g|gif|png)$/i', $o['value'])) {
                return $this->getImageObjectValue($o, $p);
            }

            return $this->getURIObjectValue($o, $p);
        }
        if ('bnode' == $o['type']) {
            return $this->getBNodeObjectValue($o, $p);
        }

        return $this->getLiteralObjectValue($o, $p);
    }

    public function getImageObjectValue($o, $p)
    {
        return '<img class="rdf-value" itemprop="'.$p.'" src="'.htmlspecialchars($o['value']).'" alt="img" />';
    }

    public function getURIObjectValue($o, $p)
    {
        $id = htmlspecialchars($o['value']);
        $label = $this->getObjectLabel($o['value']);
        /* differing href */
        $href = htmlspecialchars($this->v('href', $o['value'], $o));
        if ($id != $href) {
            return '<a class="rdf-value" itemprop="'.$p.'" href="'.$id.'" onclick="location.href=\''.$href.'\';return false">'.$label.'</a>';
        }

        return '<a class="rdf-value" itemprop="'.$p.'" href="'.$id.'">'.$label.'</a>';
        // $label = $o['value'];
        // $label = preg_replace('/^https?\:\/\/(www\.)?/', '', $label);
    }

    public function getBNodeObjectValue($o, $p)
    {
        return '<div class="rdf-value" itemprop="'.$p.'" itemscope="">'.$o['value'].'</div>';

        return '<div class="rdf-value" itemprop="'.$p.'" itemscope="">An unnamed resource</div>';
    }

    public function getLiteralObjectValue($o, $p)
    {
        return '<div class="rdf-value" itemprop="'.$p.'">'.$o['value'].'</div>';
    }

    public function getObjectLabel($id)
    {
        $r = $this->extractTermLabel($id);
        if (!$this->label_store) {
            return $r;
        }
        $q = '
      SELECT ?val WHERE {
        <'.$id.'> ?p ?val .
        FILTER(REGEX(str(?p), "(label|title|name|summary)$"))
      } LIMIT 1
    ';
        $row = $this->label_store->query($q, 'row');

        return $row ? $row['val'] : $r;
    }
}
