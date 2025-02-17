<?php
/**
 * ARC2 RSS 1.0 Serializer.
 *
 * @author Toby Inkster
 * @author Benjamin Nowack
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 *
 * @version 2010-11-16
 */
ARC2::inc('RDFXMLSerializer');

class ARC2_RSS10Serializer extends ARC2_RDFXMLSerializer
{
    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->content_header = 'application/rss+xml';
        $this->default_ns = 'http://purl.org/rss/1.0/';
        $this->type_nodes = true;
    }
}
