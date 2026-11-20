<?php
namespace OmekaTest\Entity;

use Omeka\Entity\User;
use Omeka\Entity\Vocabulary;
use Omeka\Test\TestCase;

class VocabularyTest extends TestCase
{
    protected $vocabulary;

    public function setUp(): void
    {
        $this->vocabulary = new Vocabulary;
    }

    public function testInitialState()
    {
        $this->assertNull($this->vocabulary->getId());
        $this->assertNull($this->vocabulary->getOwner());
        $this->assertNull($this->vocabulary->getNamespaceUri());
        $this->assertNull($this->vocabulary->getPrefix());
        $this->assertNull($this->vocabulary->getLabel());
        $this->assertNull($this->vocabulary->getComment());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->vocabulary->getResourceClasses()
        );
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->vocabulary->getProperties()
        );
    }

    public function testSetOwner()
    {
        $owner = new User;
        $this->vocabulary->setOwner($owner);
        $this->assertSame($owner, $this->vocabulary->getOwner());
    }

    public function testSetNamespaceUri()
    {
        $namespaceUri = 'test-namespaceUri';
        $this->vocabulary->setNamespaceUri($namespaceUri);
        $this->assertEquals($namespaceUri, $this->vocabulary->getNamespaceUri());
    }

    public function testSetPrefix()
    {
        $prefix = 'test-prefix';
        $this->vocabulary->setPrefix($prefix);
        $this->assertEquals($prefix, $this->vocabulary->getPrefix());
    }

    public function testSetlabel()
    {
        $label = 'test-label';
        $this->vocabulary->setLabel($label);
        $this->assertEquals($label, $this->vocabulary->getLabel());
    }

    public function testGetNamespaceUriForms()
    {
        $this->assertSame([
            'http://example.org/ns#',
            'http://example.org/ns/',
            'http://example.org/ns',
            'http://example.org/ns/#',
            'https://example.org/ns#',
            'https://example.org/ns/',
            'https://example.org/ns',
            'https://example.org/ns/#',
        ], Vocabulary::getNamespaceUriForms('http://example.org/ns#'));
    }

    public function testGetNamespaceUriFormsGoBothWays()
    {
        // A form of a form is a form of the first, so http://example.org/ns/#
        // and http://example.org/ns# find each other.
        $this->assertContains('http://example.org/ns/#', Vocabulary::getNamespaceUriForms('http://example.org/ns#'));
        $this->assertContains('http://example.org/ns#', Vocabulary::getNamespaceUriForms('http://example.org/ns/#'));
    }

    public function testGetNamespaceUriFormsOfASchemeInCapitals()
    {
        // As the database matches namespace URIs whatever their capitals.
        $this->assertContains('https://example.org/ns#', Vocabulary::getNamespaceUriForms('HTTP://example.org/ns#'));
        $this->assertContains('http://example.org/ns#', Vocabulary::getNamespaceUriForms('HTTPS://example.org/ns#'));
    }

    public function testGetNamespaceUriFormsStartsWithTheOneGiven()
    {
        $this->assertSame('https://example.org/ns', Vocabulary::getNamespaceUriForms('https://example.org/ns')[0]);
        $this->assertCount(8, Vocabulary::getNamespaceUriForms('https://example.org/ns'));
    }

    public function testGetNamespaceUriFormsOfAnotherScheme()
    {
        $this->assertSame(['urn:example:', 'urn:example:#', 'urn:example:/', 'urn:example:/#'], Vocabulary::getNamespaceUriForms('urn:example:'));
    }

    public function testSetComment()
    {
        $comment = 'test-comment';
        $this->vocabulary->setComment($comment);
        $this->assertEquals($comment, $this->vocabulary->getComment());
    }
}
