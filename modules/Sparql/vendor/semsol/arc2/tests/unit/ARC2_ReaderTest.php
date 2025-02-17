<?php

namespace Tests\unit;

use Tests\ARC2_TestCase;

class ARC2_ReaderTest extends ARC2_TestCase
{
    private object $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reader = \ARC2::getReader();
        $this->reader->__init();
    }

    public function testFullQualifiedURIIgnoresPreviousParts()
    {
        $parts_of_previous_uri = ['port' => 8081, 'scheme' => 'https', 'host' => 'foo.bar', 'path' => '/baz'];
        $uri_parts = $this->reader->getURIPartsFromURIAndPreviousURIParts('http://google.com:80/urlpath', $parts_of_previous_uri);

        $this->assertEquals('http', $uri_parts['scheme']);
        $this->assertEquals('80', $uri_parts['port']);
        $this->assertEquals('google.com', $uri_parts['host']);
        $this->assertEquals('/urlpath', $uri_parts['path']);
    }

    public function testWhenARelativeURIIsPassedSchemeHostAndPortAreInferredFromPreviousParts()
    {
        $parts_of_previous_uri = ['port' => 8081, 'scheme' => 'https', 'host' => 'foo.bar', 'path' => '/baz'];
        $uri_parts = $this->reader->getURIPartsFromURIAndPreviousURIParts('/urlbits/andbobs', $parts_of_previous_uri);

        $this->assertEquals('https', $uri_parts['scheme']);
        $this->assertEquals('8081', $uri_parts['port']);
        $this->assertEquals('foo.bar', $uri_parts['host']);
        $this->assertEquals('/urlbits/andbobs', $uri_parts['path']);
    }

    public function testWhenTheSchemeChangesButPortIsNotExplicitThePortIsInferredFromTheSchemeNotThePreviousParts()
    {
        $parts_of_previous_uri = ['port' => 8081, 'scheme' => 'https', 'host' => 'foo.bar', 'path' => '/baz'];
        $uri_parts = $this->reader->getURIPartsFromURIAndPreviousURIParts('http://bbc.co.uk/news', $parts_of_previous_uri);

        $this->assertEquals('http', $uri_parts['scheme']);
        $this->assertEquals('80', $uri_parts['port']);
        $this->assertEquals('bbc.co.uk', $uri_parts['host']);
        $this->assertEquals('/news', $uri_parts['path']);
    }

    public function testWhenTheSchemeHasNotChangedAndPortIsNotExplicitThePortIsInferredFromThePreviousParts()
    {
        /* not totally convinced this is actually the right behaviour. Possibly if there is a scheme but no port then the scheme should always set the port */
        $parts_of_previous_uri = ['port' => 8081, 'scheme' => 'https', 'host' => 'foo.bar', 'path' => '/baz'];
        $uri_parts = $this->reader->getURIPartsFromURIAndPreviousURIParts('https://bbc.co.uk/news', $parts_of_previous_uri);

        $this->assertEquals('https', $uri_parts['scheme']);
        $this->assertEquals('8081', $uri_parts['port']);
        $this->assertEquals('bbc.co.uk', $uri_parts['host']);
        $this->assertEquals('/news', $uri_parts['path']);
    }
}
