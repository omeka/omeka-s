<?php
/*
@homepage <https://github.com/semsol/arc2>
@license W3C Software License and GPL

class:    ARC2 microformats Extractor
author:   Benjamin Nowack
version:  2010-11-16
*/

ARC2::inc('ARC2_PoshRdfExtractor');

class ARC2_MicroformatsExtractor extends ARC2_PoshRdfExtractor
{
    public function __construct($a, &$caller)
    {
        parent::__construct($a, $caller);
    }

    public function __init()
    {
        parent::__init();
        $this->terms = $this->getTerms();
        $this->ns_prefix = 'mf';
        $this->a['ns']['mf'] = 'http://poshrdf.org/ns/mf#';
        $this->caller->detected_formats['posh-rdf'] = 1;
    }

    public function preProcessNode($n)
    {
        if (!$n) {
            return $n;
        }
        /* remove existing poshRDF hooks */
        if (!is_array($n['a'])) {
            $n['a'] = [];
        }
        $n['a']['class'] = isset($n['a']['class']) ? preg_replace('/\s?rdf\-(s|p|o|o-xml)/', '', $n['a']['class']) : '';
        if (!isset($n['a']['rel'])) {
            $n['a']['rel'] = '';
        }
        /* inject poshRDF hooks */
        foreach ($this->terms as $term => $infos) {
            if ((!in_array('rel', $infos) && $this->hasClass($n, $term)) || $this->hasRel($n, $term)) {
                if ($this->v('scope', '', $infos)) {
                    $infos[] = 'p';
                }
                foreach (['s', 'p', 'o', 'o-xml'] as $type) {
                    if (in_array($type, $infos)) {
                        $n['a']['class'] .= ' rdf-'.$type;
                        $n['a']['class'] = preg_replace('/(^|\s)'.$term.'(\s|$)/s', '\\1mf-'.$term.'\\2', $n['a']['class']);
                        $n['a']['rel'] = preg_replace('/(^|\s)'.$term.'(\s|$)/s', '\\1mf-'.$term.'\\2', $n['a']['rel']);
                    }
                }
            }
        }
        $n['a']['class m'] = preg_split('/ /', $n['a']['class']);
        $n['a']['rel m'] = preg_split('/ /', $n['a']['rel']);

        return $n;
    }

    public function getPredicates($n, $ns)
    {
        $ns = ['mf' => $ns['mf']];

        return parent::getPredicates($n, $ns);
    }

    public function tweakObject($o, $p, $ct)
    {
        $ns = $ct['ns']['mf'];
        /* rel-tag, skill => extract from URL */
        if (in_array($p, [$ns.'tag', $ns.'skill'])) {
            $o = preg_replace('/^.*\/([^\/]+)/', '\\1', trim($o, '/'));
            $o = urldecode(rawurldecode($o));
        }

        return $o;
    }

    public function getTerms()
    {
        /* no need to define 'p' if scope is not empty */
        return [
            'acquaintance' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'additional-name' => ['o', 'scope' => ['n']],
            'adr' => ['s', 'o', 'scope' => ['_doc', 'vcard']],
            'affiliation' => ['s', 'o', 'scope' => ['hresume']],
            'author' => ['s', 'o', 'scope' => ['hentry']],
            'bday' => ['o', 'scope' => ['vcard']],
            'bio' => ['o', 'scope' => ['vcard']],
            'best' => ['o', 'scope' => ['hreview']],
            'bookmark' => ['o', 'scope' => ['_doc', 'hentry', 'hreview']],
            'class' => ['o', 'scope' => ['vcard', 'vevent']],
            'category' => ['o', 's', 'scope' => ['vcard', 'vevent']],
            'child' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'co-resident' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'co-worker' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'colleague' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'contact' => ['o', 'scope' => ['_doc', 'hresume', 'hentry']],
            'country-name' => ['o', 'scope' => ['adr']],
            'crush' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'date' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'description' => ['o', 'scope' => ['vevent', 'hreview', 'xfolkentry']],
            'directory' => ['o', 'rel', 'scope' => ['_doc', 'hfeed', 'hentry', 'hreview']],
            'dtend' => ['o', 'scope' => ['vevent']],
            'dtreviewed' => ['o', 'scope' => ['hreview']],
            'dtstamp' => ['o', 'scope' => ['vevent']],
            'dtstart' => ['o', 'scope' => ['vevent']],
            'duration' => ['o', 'scope' => ['vevent']],
            'education' => ['s', 'o', 'scope' => ['hresume']],
            'email' => ['s', 'o', 'scope' => ['vcard']],
            'entry-title' => ['o', 'scope' => ['hentry']],
            'entry-content' => ['o-xml', 'scope' => ['hentry']],
            'entry-summary' => ['o', 'scope' => ['hentry']],
            'experience' => ['s', 'o', 'scope' => ['hresume']],
            'extended-address' => ['o', 'scope' => ['adr']],
            'family-name' => ['o', 'scope' => ['n']],
            'fn' => ['o', 'plain', 'scope' => ['vcard', 'item']],
            'friend' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'geo' => ['s', 'scope' => ['_doc', 'vcard', 'vevent']],
            'given-name' => ['o', 'scope' => ['n']],
            'hentry' => ['s', 'o', 'scope' => ['_doc', 'hfeed']],
            'hfeed' => ['s', 'scope' => ['_doc']],
            'honorific-prefix' => ['o', 'scope' => ['n']],
            'honorific-suffix' => ['o', 'scope' => ['n']],
            'hresume' => ['s', 'scope' => ['_doc']],
            'hreview' => ['s', 'scope' => ['_doc']],
            'item' => ['s', 'scope' => ['hreview']],
            'key' => ['o', 'scope' => ['vcard']],
            'kin' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'label' => ['o', 'scope' => ['vcard']],
            'last-modified' => ['o', 'scope' => ['vevent']],
            'latitude' => ['o', 'scope' => ['geo']],
            'license' => ['o', 'rel', 'scope' => ['_doc', 'hfeed', 'hentry', 'hreview']],
            'locality' => ['o', 'scope' => ['adr']],
            'location' => ['o', 'scope' => ['vevent']],
            'logo' => ['o', 'scope' => ['vcard']],
            'longitude' => ['o', 'scope' => ['geo']],
            'mailer' => ['o', 'scope' => ['vcard']],
            'me' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'met' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'muse' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'n' => ['s', 'o', 'scope' => ['vcard']],
            'neighbor' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'nickname' => ['o', 'plain', 'scope' => ['vcard']],
            'nofollow' => ['o', 'rel', 'scope' => ['_doc']],
            'note' => ['o', 'scope' => ['vcard']],
            'org' => ['o', 'xplain', 'scope' => ['vcard']],
            'parent' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'permalink' => ['o', 'scope' => ['hreview']],
            'photo' => ['o', 'scope' => ['vcard', 'item']],
            'post-office-box' => ['o', 'scope' => ['adr']],
            'postal-code' => ['o', 'scope' => ['adr']],
            'publication' => ['s', 'o', 'scope' => ['hresume']],
            'published' => ['o', 'scope' => ['hentry']],
            'rating' => ['o', 'scope' => ['hreview']],
            'region' => ['o', 'scope' => ['adr']],
            'rev' => ['o', 'scope' => ['vcard']],
            'reviewer' => ['s', 'o', 'scope' => ['hreview']],
            'role' => ['o', 'plain', 'scope' => ['vcard']],
            'sibling' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'skill' => ['o', 'scope' => ['hresume']],
            'sort-string' => ['o', 'scope' => ['vcard']],
            'sound' => ['o', 'scope' => ['vcard']],
            'spouse' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'status' => ['o', 'plain', 'scope' => ['vevent']],
            'street-address' => ['o', 'scope' => ['adr']],
            'summary' => ['o', 'scope' => ['vevent', 'hreview', 'hresume']],
            'sweetheart' => ['o', 'rel', 'scope' => ['_doc', 'hentry']],
            'tag' => ['o', 'rel', 'scope' => ['_doc', 'category', 'hfeed', 'hentry', 'skill', 'hreview', 'xfolkentry']],
            'taggedlink' => ['o', 'scope' => ['xfolkentry']],
            'title' => ['o', 'scope' => ['vcard']],
            'type' => ['o', 'scope' => ['adr', 'email', 'hreview', 'tel']],
            'tz' => ['o', 'scope' => ['vcard']],
            'uid' => ['o', 'scope' => ['vcard', 'vevent']],
            'updated' => ['o', 'scope' => ['hentry']],
            'url' => ['o', 'scope' => ['vcard', 'vevent', 'item']],
            'value' => ['o', 'scope' => ['email', 'adr', 'tel']],
            'vcard' => ['s', 'scope' => ['author', 'reviewer', 'affiliation', 'contact']],
            'version' => ['o', 'scope' => ['hreview']],
            'vevent' => ['s', 'scope' => ['_doc']],
            'worst' => ['o', 'scope' => ['hreview']],
            'xfolkentry' => ['s', 'scope' => ['_doc']],
        ];
    }
}
