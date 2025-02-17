<?php
/**
 * ARC2 result format detection.
 *
 * @author Benjamin Nowack
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 *
 * @version 2010-11-16
 */
function ARC2_getPreferredFormat($default = 'plain')
{
    $formats = [
        'html' => 'HTML', 'text/html' => 'HTML', 'xhtml+xml' => 'HTML',
        'rdfxml' => 'RDFXML', 'rdf+xml' => 'RDFXML',
        'ntriples' => 'NTriples',
        'rdf+n3' => 'Turtle', 'x-turtle' => 'Turtle', 'turtle' => 'Turtle', 'text/turtle' => 'Turtle',
        'rdfjson' => 'RDFJSON', 'json' => 'RDFJSON',
        'xml' => 'XML',
        'legacyjson' => 'LegacyJSON',
    ];
    $prefs = [];
    $o_vals = [];
    /* accept header */
    $vals = explode(',', $_SERVER['HTTP_ACCEPT']);
    if ($vals) {
        foreach ($vals as $val) {
            if (preg_match('/(rdf\+n3|(x\-|text\/)turtle|rdf\+xml|text\/html|xhtml\+xml|xml|json)/', $val, $m)) {
                $o_vals[$m[1]] = 1;
                if (preg_match('/\;q\=([0-9\.]+)/', $val, $sub_m)) {
                    $o_vals[$m[1]] = 1 * $sub_m[1];
                }
            }
        }
    }
    /* arg */
    if (isset($_GET['format'])) {
        $o_vals[$_GET['format']] = 1.1;
    }
    /* rank */
    arsort($o_vals);
    foreach ($o_vals as $val => $prio) {
        $prefs[] = $val;
    }
    /* default */
    $prefs[] = $default;
    foreach ($prefs as $pref) {
        if (isset($formats[$pref])) {
            return $formats[$pref];
        }
    }
}
