<?php
namespace OmekaTest\View\Helper;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\View\Helper\TextDiff;
use Omeka\Test\TestCase;

class TextDiffTest extends TestCase
{
    /**
     * Two texts' parts as text, with what differs in brackets.
     */
    protected static function marked($from, $to)
    {
        return array_map(function (array $parts) {
            return implode('', array_map(function ($part) {
                return $part[1] ? '[' . $part[0] . ']' : $part[0];
            }, $parts));
        }, (new TextDiff)->words($from, $to));
    }

    /**
     * @dataProvider texts
     */
    public function testWhatDiffersIsMarkedInEachText($from, $to, $markedFrom, $markedTo)
    {
        $this->assertSame([$markedFrom, $markedTo], self::marked($from, $to));
    }

    public function texts()
    {
        return [
            'a word replaced' => ['A grant to enable him/her to study.', 'A grant to enable them to study.', 'A grant to enable [him/her] to study.', 'A grant to enable [them] to study.'],
            'words added at the start' => ['This class is all.', 'Scope note: This class is all.', 'This class is all.', '[Scope note:] This class is all.'],
            'words removed at the end' => ['One two three', 'One two', 'One two[ three]', 'One two'],
            'a run of words is one part' => ['A person.', 'A human being.', 'A [person.]', 'A [human being.]'],
            'nothing in common' => ['a b c', 'x y z', '[a b c]', '[x y z]'],
            'the same' => ['same text', 'same text', 'same text', 'same text'],
            'words moved' => ['A b c d', 'b c d e', '[A] b c d', 'b c d[ e]'],
            'other than Latin' => ['Ein Ding', 'Eine Sache', '[Ein Ding]', '[Eine Sache]'],
        ];
    }

    /**
     * @dataProvider spacings
     */
    public function testAChangeInSpacingAloneIsMarkedAsThat($from, $to, $markedFrom, $markedTo)
    {
        $this->assertSame([$markedFrom, $markedTo], self::marked($from, $to));
    }

    public function spacings()
    {
        return [
            'two spaces for one' => ['one two three', 'one  two three', 'one[ ]two three', 'one[  ]two three'],
            'a space at the end' => ['trailing', 'trailing ', 'trailing', 'trailing[ ]'],
            'a line break for a space' => ['line one line two', "line one\nline two", 'line one[ ]line two', "line one[\n]line two"],
            'a space at the start' => ['text', ' text', 'text', '[ ]text'],
        ];
    }

    public function testAPartsTextIsTheTextWhateverDiffers()
    {
        foreach ([['', ''], ['', 'new'], ['old', ''], ["  a\tb \n", "a b"], ["Caf\xe9 au lait", 'Café au lait']] as [$from, $to]) {
            [$fromParts, $toParts] = (new TextDiff)->words($from, $to);
            $this->assertSame($from, implode('', array_column($fromParts, 0)));
            $this->assertSame($to, implode('', array_column($toParts, 0)));
        }
    }

    public function testVeryLongTextsWithLittleInCommonAreNotMatchedUp()
    {
        $words = range(1, TextDiff::MAX_WORDS + 1);
        $from = 'Start ' . implode(' ', $words) . ' end';
        $to = 'Start ' . implode(' ', array_reverse($words)) . ' end';
        [$markedFrom, $markedTo] = self::marked($from, $to);
        // What they share at their start and end still is.
        $this->assertStringStartsWith('Start [1 2 3', $markedFrom);
        $this->assertStringEndsWith('2 1] end', $markedTo);
        $this->assertSame(1, substr_count($markedFrom, '['));
    }

    public function testATextJustUnderTheLimitIsMatchedUpQuickly()
    {
        $words = range(1, TextDiff::MAX_WORDS - 1);
        $start = microtime(true);
        (new TextDiff)->words(implode(' ', $words), implode(' ', array_reverse($words)));
        $this->assertLessThan(2, microtime(true) - $start);
    }

    public function testMarksWhatDiffersAsHtml()
    {
        $textDiff = new TextDiff;
        $textDiff->setView(new PhpRenderer);
        // Escaped, with a line break within a change given a sign.
        $this->assertSame([
            'a <del>&lt;b&gt;</del> c',
            "a <ins>d<span class=\"text-diff-line-break\" aria-hidden=\"true\">↵</span>\ne</ins> c",
        ], $textDiff('a <b> c', "a d\ne c"));
    }
}
