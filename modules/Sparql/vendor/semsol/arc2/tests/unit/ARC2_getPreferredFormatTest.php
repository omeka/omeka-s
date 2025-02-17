<?php

namespace Tests\unit;

use Tests\ARC2_TestCase;

class ARC2_getPreferredFormatTest extends ARC2_TestCase
{
    protected function setUp(): void
    {
        // fix warning about unset SCRIPT_NAME index in PHPUnit
        // Notice: Undefined index: SCRIPT_NAME in /var/www/html/ARC2/vendor/phpunit/phpunit/src/Util/Filter.php on line 27
        $_SERVER['SCRIPT_NAME'] = '';
    }

    public function testGetPreferredFormat()
    {
        $_SERVER['HTTP_ACCEPT'] = '';
        $actual = \ARC2::getPreferredFormat('xml');
        $this->assertEquals('XML', $actual);

        $actual = \ARC2::getPreferredFormat('foo');
        $this->assertNull($actual);

        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $actual = \ARC2::getPreferredFormat();
        $this->assertEquals('HTML', $actual);

        $_SERVER['HTTP_ACCEPT'] = 'application/rdf+xml,text/html;q=0.9,*/*;q=0.8';
        $actual = \ARC2::getPreferredFormat();
        $this->assertEquals('RDFXML', $actual);
    }
}
