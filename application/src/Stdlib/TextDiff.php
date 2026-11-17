<?php
namespace Omeka\Stdlib;

/**
 * What differs between two texts, word by word, for the vocabulary review
 * page.
 */
class TextDiff
{
    /**
     * The most words of each text's middle that are matched up. Past this,
     * the middles differ as a whole, so long texts can't hold up the page.
     */
    const MAX_WORDS = 600;

    /**
     * Two texts, each as the parts it shares with the other and the parts it
     * doesn't. A change in spacing alone shows as that.
     *
     * @param string $from
     * @param string $to
     * @return array [parts of $from, parts of $to], each part [text, whether
     *   it differs]; a text's parts, joined, are the text
     */
    public static function words($from, $to)
    {
        [$a, $b, $matchA, $matchB] = self::aligned($from, $to);
        return [self::parts($a, $b, $matchA), self::parts($b, $a, $matchB)];
    }

    /**
     * Two texts as their words, and which word of each matches which of the
     * other's.
     *
     * @return array [units of $from, units of $to, indexes of $to's words by
     *   the indexes of $from's they match, and the reverse]
     */
    protected static function aligned($from, $to)
    {
        $a = self::units($from);
        $b = self::units($to);
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
            : self::shared(array_slice($wordsA, $start, $lengthA), array_slice($wordsB, $start, $lengthB));
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
    protected static function units($text)
    {
        $text = (string) $text;
        if (!preg_match_all('/(\S*)(\s*)/u', $text, $matches, PREG_SET_ORDER)) {
            // Text that isn't valid UTF-8 is one word, and differs as a whole.
            return $text === '' ? [] : [[$text, '']];
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
    protected static function shared(array $a, array $b)
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
        for ($i = 0, $j = 0; $i < $n && $j < $m;) {
            if ($a[$i] === $b[$j]) {
                $pairs[$i++] = $j++;
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
    protected static function parts(array $units, array $others, array $match)
    {
        $marked = [];
        foreach ($units as $i => [$word, $space]) {
            $matched = isset($match[$i]);
            if ($word !== '') {
                $marked[] = [$word, !$matched];
            }
            if ($space !== '') {
                // Space a text starts with has no word to differ with it.
                $marked[] = [$space, $matched
                    ? $space !== $others[$match[$i]][1]
                    : $word === '' || (isset($units[$i + 1]) && !isset($match[$i + 1]))];
            }
        }
        return self::joined($marked);
    }

    /**
     * Parts with neighbors of the same kind joined.
     */
    protected static function joined(array $marked)
    {
        $parts = [];
        foreach ($marked as [$text, $kind]) {
            $last = count($parts) - 1;
            if ($last >= 0 && $parts[$last][1] === $kind) {
                $parts[$last][0] .= $text;
            } else {
                $parts[] = [$text, $kind];
            }
        }
        return $parts;
    }
}
