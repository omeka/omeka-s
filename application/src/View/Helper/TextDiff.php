<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for marking what differs between two texts, word by word, such
 * as a label or comment and its new version.
 */
class TextDiff extends AbstractHelper
{
    /**
     * The most words matched one by one in each text's middle, the part
     * between what the two share at their start and end. A longer middle is
     * marked changed as a whole, so long texts can't hold up the page.
     */
    const MAX_WORDS = 600;

    /**
     * Mark what differs between two texts as HTML: struck out in the first
     * (del), underlined in the second (ins), so it shows without color. The
     * texts are escaped, and a changed line break, which wouldn't otherwise
     * show, gets a sign.
     *
     * @param string $from
     * @param string $to
     * @return string[] [the first text's HTML, the second's]
     */
    public function __invoke($from, $to)
    {
        [$fromParts, $toParts] = $this->getParts($from, $to);
        return [$this->mark($fromParts, 'del'), $this->mark($toParts, 'ins')];
    }

    /**
     * Two texts, each as the parts it shares with the other and the parts it
     * doesn't. Where only the space between two shared words differs, just
     * that space is marked.
     *
     * @param string $from
     * @param string $to
     * @return array [parts of $from, parts of $to], each part [text, whether
     *   it differs]; a text's parts, joined, are the text
     */
    protected function getParts($from, $to)
    {
        [$a, $b, $matchA, $matchB] = $this->align($from, $to);
        return [$this->getTextParts($a, $b, $matchA), $this->getTextParts($b, $a, $matchB)];
    }

    /**
     * Two texts as their words, and which word of each matches which of the
     * other's.
     *
     * @return array [units of $from, units of $to, indexes of $to's words by
     *   the indexes of $from's they match, and the reverse]
     */
    protected function align($from, $to)
    {
        $a = $this->getUnits($from);
        $b = $this->getUnits($to);
        $wordsA = array_column($a, 0);
        $wordsB = array_column($b, 0);
        // What the texts share at their start and end needn't be matched.
        $start = 0;
        while (isset($wordsA[$start], $wordsB[$start]) && $wordsA[$start] === $wordsB[$start]) {
            $start++;
        }
        $end = 0;
        while (count($a) - $end > $start && count($b) - $end > $start
            && $wordsA[count($a) - 1 - $end] === $wordsB[count($b) - 1 - $end]
        ) {
            $end++;
        }
        $lengthA = count($a) - $start - $end;
        $lengthB = count($b) - $start - $end;
        $pairs = $lengthA > self::MAX_WORDS || $lengthB > self::MAX_WORDS
            ? []
            : $this->getMatches(array_slice($wordsA, $start, $lengthA), array_slice($wordsB, $start, $lengthB));
        // Each text's words by the word of the other they match.
        $matchA = [];
        for ($i = 0; $i < $start; $i++) {
            $matchA[$i] = $i;
        }
        foreach ($pairs as $i => $j) {
            $matchA[$start + $i] = $start + $j;
        }
        for ($i = 1; $i <= $end; $i++) {
            $matchA[count($a) - $i] = count($b) - $i;
        }
        return [$a, $b, $matchA, array_flip($matchA)];
    }

    /**
     * A text as its words, each with the space that follows it. Space the
     * text starts with follows an empty word.
     *
     * @return array [[word, space], …]
     */
    protected function getUnits($text)
    {
        $text = (string) $text;
        if (!preg_match_all('/(\S*)(\s*)/u', $text, $matches, PREG_SET_ORDER)) {
            // Text that isn't valid UTF-8 is one word, and differs as a whole.
            return [[$text, '']];
        }
        $units = [];
        foreach ($matches as [$all, $word, $space]) {
            if ($all !== '') {
                $units[] = [$word, $space];
            }
        }
        return $units;
    }

    /**
     * The words two lists share, in order: their longest common subsequence.
     *
     * @return array Indexes of $b by the indexes of $a they match
     */
    protected function getMatches(array $a, array $b)
    {
        $n = count($a);
        $m = count($b);
        if (!$n || !$m) {
            return [];
        }
        // Lengths of the longest common subsequence of what follows each
        // pair of positions.
        $lengths = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lengths[$i][$j] = $a[$i] === $b[$j]
                    ? $lengths[$i + 1][$j + 1] + 1
                    : max($lengths[$i + 1][$j], $lengths[$i][$j + 1]);
            }
        }
        $pairs = [];
        $i = 0;
        $j = 0;
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $pairs[$i] = $j;
                $i++;
                $j++;
            } elseif ($lengths[$i + 1][$j] >= $lengths[$i][$j + 1]) {
                $i++;
            } else {
                $j++;
            }
        }
        return $pairs;
    }

    /**
     * A text's parts, given which of the other's words its own match. A run
     * of changed words, with the space between them, is one part.
     */
    protected function getTextParts(array $units, array $others, array $match)
    {
        $marked = [];
        foreach ($units as $i => [$word, $space]) {
            $matched = isset($match[$i]);
            if ($word !== '') {
                $marked[] = [$word, !$matched];
            }
            if ($space !== '') {
                if ($matched) {
                    $changed = $space !== $others[$match[$i]][1];
                } else {
                    // The space after an unmatched word is a change where the
                    // next word is unmatched too, keeping a run of changes in
                    // one part, or where the word is empty, at a text's start.
                    $changed = $word === '' || (isset($units[$i + 1]) && !isset($match[$i + 1]));
                }
                $marked[] = [$space, $changed];
            }
        }
        return $this->joinParts($marked);
    }

    /**
     * Parts with neighbors that both differ, or both don't, joined.
     */
    protected function joinParts(array $marked)
    {
        $parts = [];
        foreach ($marked as [$text, $differs]) {
            $last = count($parts) - 1;
            if ($last >= 0 && $parts[$last][1] === $differs) {
                $parts[$last][0] .= $text;
            } else {
                $parts[] = [$text, $differs];
            }
        }
        return $parts;
    }

    /**
     * A text's parts as HTML, escaped, with those that differ in a tag.
     *
     * @param array $parts As getTextParts() gives them
     * @param string $tag "del" or "ins"
     * @return string
     */
    protected function mark(array $parts, $tag)
    {
        $escape = $this->getView()->plugin('escapeHtml');
        $html = '';
        foreach ($parts as [$text, $differs]) {
            $text = $escape($text);
            $html .= $differs
                ? sprintf('<%1$s>%2$s</%1$s>', $tag, str_replace("\n", '<span class="text-diff-line-break" aria-hidden="true">↵</span>' . "\n", $text))
                : $text;
        }
        return $html;
    }
}
