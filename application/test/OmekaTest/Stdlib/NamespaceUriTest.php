<?php
namespace OmekaTest\Stdlib;

use Omeka\Stdlib\NamespaceUri;
use Omeka\Test\TestCase;

class NamespaceUriTest extends TestCase
{
    public function testGetForms()
    {
        $this->assertSame([
            'http://example.org/ns#',
            'http://example.org/ns/',
            'http://example.org/ns',
            'https://example.org/ns#',
            'https://example.org/ns/',
            'https://example.org/ns',
        ], NamespaceUri::getForms('http://example.org/ns#'));
    }

    public function testGetFormsStartsWithTheOneGiven()
    {
        $this->assertSame('https://example.org/ns', NamespaceUri::getForms('https://example.org/ns')[0]);
        $this->assertCount(6, NamespaceUri::getForms('https://example.org/ns'));
    }

    public function testGetFormsOfAnotherScheme()
    {
        $this->assertSame(['urn:example:', 'urn:example:#', 'urn:example:/'], NamespaceUri::getForms('urn:example:'));
    }
}
