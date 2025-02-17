<?php

namespace Tests\unit;

use Tests\ARC2_TestCase;

\define('TESTS_FOLDER_PATH', __DIR__.'/../');

class ARC2_getFormatTest extends ARC2_TestCase
{
    public function testGetFormatWithAtom()
    {
        $data = file_get_contents(TESTS_FOLDER_PATH.'data/atom/feed.atom');

        $actual = \ARC2::getFormat($data, 'application/atom+xml');
        $this->assertEquals('atom', $actual);

        $actual = \ARC2::getFormat($data);
        $this->assertEquals('atom', $actual);
    }

    public function testGetFormatWithRdfXml()
    {
        $data = file_get_contents(TESTS_FOLDER_PATH.'data/rdfxml/planetrdf-bloggers.rdf');

        $actual = \ARC2::getFormat($data, 'application/rdf+xml');
        $this->assertEquals('rdfxml', $actual);

        $actual = \ARC2::getFormat($data);
        $this->assertEquals('rdfxml', $actual);
    }

    public function testGetFormatWithTurtle()
    {
        $data = file_get_contents(TESTS_FOLDER_PATH.'data/turtle/manifest.ttl');

        $actual = \ARC2::getFormat($data, 'text/turtle');
        $this->assertEquals('turtle', $actual);

        $actual = \ARC2::getFormat($data);
        $this->assertEquals('turtle', $actual);
    }

    public function testGetFormatWithJson()
    {
        $data = file_get_contents(TESTS_FOLDER_PATH.'data/json/sparql-select-result.json');

        $actual = \ARC2::getFormat($data, 'application/json');
        $this->assertEquals('json', $actual);

        $actual = \ARC2::getFormat($data);
        $this->assertEquals('json', $actual);

        $data = file_get_contents(TESTS_FOLDER_PATH.'data/json/crunchbase-facebook.js');

        $actual = \ARC2::getFormat($data);
        $this->assertEquals('cbjson', $actual);
    }

    public function testGetFormatWithN3()
    {
        $data = file_get_contents(TESTS_FOLDER_PATH.'data/nt/test.nt');

        $actual = \ARC2::getFormat($data, 'application/rdf+n3');
        $this->assertEquals('n3', $actual);

        $actual = \ARC2::getFormat($data, '', 'n3');
        $this->assertEquals('n3', $actual);
    }

    public function testGetFormatWithNTriples()
    {
        $data = file_get_contents(TESTS_FOLDER_PATH.'data/nt/test.nt');

        $actual = \ARC2::getFormat($data);
        $this->assertEquals('ntriples', $actual);

        $actual = \ARC2::getFormat($data, '', 'nt');
        $this->assertEquals('ntriples', $actual);
    }
}
