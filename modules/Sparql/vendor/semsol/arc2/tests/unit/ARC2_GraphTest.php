<?php

namespace Tests\unit;

use Tests\ARC2_TestCase;

class ARC2_GraphTest extends ARC2_TestCase
{
    private object $obj;

    /**
     * @var array<mixed>
     */
    private array $res1;

    /**
     * @var array<mixed>
     */
    private array $res2;

    /**
     * @var array<mixed>
     */
    private array $res3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->obj = \ARC2::getGraph();
        $this->res1 = [
            'http://example.com/s1' => [
                'http://example.com/p1' => [
                    ['value' => 'o1', 'type' => 'literal'],
                    ['value' => 'http://example.com/o1', 'type' => 'uri'],
                ],
            ],
        ];
        $this->res2 = [
            'http://example.com/s2' => [
                'http://example.com/p2' => [
                    ['value' => 'o2', 'type' => 'literal'],
                    ['value' => 'http://example.com/o2', 'type' => 'uri'],
                ],
            ],
        ];
        $this->res3 = [
            'http://example.com/s1' => [
                'http://example.com/p3' => [
                    ['value' => 'o3', 'type' => 'literal'],
                ],
            ],
        ];
    }

    public function testSetIndex()
    {
        $actual = $this->obj->setIndex($this->res1);
        $this->assertSame($this->obj, $actual);

        $actual = $this->obj->getIndex();
        $this->assertEquals($this->res1, $actual);
    }

    public function testGetIndex()
    {
        $actual = $this->obj->getIndex();
        $this->assertTrue(\is_array($actual), 'should return array');
    }

    public function testAddIndex()
    {
        $actual = $this->obj->addIndex($this->res1);
        $this->assertSame($this->obj, $actual);

        $actual = $this->obj->getIndex();
        $this->assertEquals($this->res1, $actual);

        $this->obj->addIndex($this->res1);
        $actual = $this->obj->getIndex();
        $this->assertEquals($this->res1, $actual);

        $this->obj->addIndex($this->res2);
        $actual = $this->obj->getIndex();
        $this->assertEquals(array_merge($this->res1, $this->res2), $actual);

        $this->obj->addIndex($this->res3);
        $actual = $this->obj->getIndex();
        $this->assertEquals(2, \count(array_keys($actual['http://example.com/s1'])));
        $this->assertEquals(1, \count(array_keys($actual['http://example.com/s2'])));
    }

    public function testAddGraph()
    {
        $this->obj->addIndex($this->res1);
        $g2 = \ARC2::getGraph()->addIndex($this->res2);

        $actual = $this->obj->addGraph($g2);
        $this->assertSame($this->obj, $actual);

        $actual = $this->obj->getIndex();
        $this->assertEquals(array_merge($this->res1, $this->res2), $actual);
    }

    public function testAddGraphWithNamespaces()
    {
        $g2 = \ARC2::getGraph()->setPrefix('ex', 'http://example.com/');

        $actual = $this->obj->addGraph($g2);
        $this->assertArrayHasKey('ex', $actual->ns);
    }

    public function testAddRdf()
    {
        $rdf = $this->obj->toTurtle($this->res1);
        $this->obj->addRdf($rdf, 'turtle');
        $actual = $this->obj->getIndex();
        $this->assertEquals($this->res1, $actual);

        $rdf = json_encode($this->res2);
        $this->obj->addRdf($rdf, 'json');
        $actual = $this->obj->getIndex();
        $this->assertEquals(array_merge($this->res1, $this->res2), $actual);
    }

    public function testHasSubject()
    {
        $actual = $this->obj->setIndex($this->res1);
        $this->assertTrue($actual->hasSubject('http://example.com/s1'));
        $this->assertFalse($actual->hasSubject('http://example.com/s2'));
    }

    public function testHasTriple()
    {
        $actual = $this->obj->setIndex($this->res1);
        $this->assertTrue($actual->hasTriple('http://example.com/s1', 'http://example.com/p1', 'o1'));
        $this->assertFalse($actual->hasTriple('http://example.com/s1', 'http://example.com/p1', 'o2'));
        $this->assertTrue($actual->hasTriple('http://example.com/s1', 'http://example.com/p1', ['value' => 'o1', 'type' => 'literal']));
        $this->assertFalse($actual->hasTriple('http://example.com/s1', 'http://example.com/p1', ['value' => 'o1', 'type' => 'uri']));
    }

    public function testHasLiteralTriple()
    {
        $actual = $this->obj->setIndex($this->res2);
        $this->assertTrue($actual->hasLiteralTriple('http://example.com/s2', 'http://example.com/p2', 'o2'));
        $this->assertFalse($actual->hasLiteralTriple('http://example.com/s1', 'http://example.com/p1', 'o2'));
    }

    public function testHasLinkTriple()
    {
        $actual = $this->obj->setIndex($this->res2);
        $this->assertTrue($actual->hasLinkTriple('http://example.com/s2', 'http://example.com/p2', 'http://example.com/o2'));
        $this->assertFalse($actual->hasLinkTriple('http://example.com/s2', 'http://example.com/p2', 'o2'));
    }

    public function testAddTriple()
    {
        $actual = $this->obj->addTriple('_:s1', '_:p1', 'o1');
        $this->assertTrue($actual->hasLiteralTriple('_:s1', '_:p1', 'o1'));

        $actual = $this->obj->addTriple('_:s1', '_:p1', 'o1', 'bnode');
        $this->assertTrue($actual->hasLinkTriple('_:s1', '_:p1', 'o1'));
    }

    public function testGetSubjects()
    {
        $g = $this->obj->setIndex($this->res1);

        $actual = $g->getSubjects();
        $this->assertEquals(['http://example.com/s1'], $actual);

        $actual = $g->getSubjects('p');
        $this->assertEquals([], $actual);

        $actual = $g->getSubjects('http://example.com/p1');
        $this->assertEquals(['http://example.com/s1'], $actual);

        $actual = $g->getSubjects(null, 'o');
        $this->assertEquals([], $actual);

        $actual = $g->getSubjects(null, 'o1');
        $this->assertEquals(['http://example.com/s1'], $actual);

        $actual = $g->getSubjects(null, ['value' => 'http://example.com/o1', 'type' => 'uri']);
        $this->assertEquals(['http://example.com/s1'], $actual);

        $actual = $g->getSubjects('http://example.com/p1', 'o');
        $this->assertEquals([], $actual);

        $actual = $g->getSubjects('http://example.com/p1', 'o1');
        $this->assertEquals(['http://example.com/s1'], $actual);
    }

    public function testGetPredicates()
    {
        $g = $this->obj->setIndex($this->res1)->addIndex($this->res2);

        $actual = $g->getPredicates();
        $this->assertEquals(['http://example.com/p1', 'http://example.com/p2'], $actual);

        $actual = $g->getPredicates('http://example.com/s2');
        $this->assertEquals(['http://example.com/p2'], $actual);
    }

    public function testGetObjects()
    {
        $actual = $this->obj->setIndex($this->res1)->getObjects('http://example.com/s1', 'http://example.com/p1', true);
        $this->assertEmpty(array_diff(['http://example.com/o1', 'o1'], $actual));
        $this->assertEmpty(array_diff($actual, ['http://example.com/o1', 'o1']));

        $actual = $this->obj->setIndex($this->res3)->getObjects('http://example.com/s1', 'http://example.com/p3');
        $this->assertEquals([['value' => 'o3', 'type' => 'literal']], $actual);
    }

    public function testGetObject()
    {
        $actual = $this->obj->setIndex($this->res1)->getObject('http://example.com/s1', 'http://example.com/p1', true);
        $this->assertEquals('o1', $actual);

        $actual = $this->obj->setIndex($this->res3)->getObject('http://example.com/s1', 'http://example.com/p3');
        $this->assertEquals(['value' => 'o3', 'type' => 'literal'], $actual);
    }

    public function testGetNtriples()
    {
        $actual = $this->obj->setIndex($this->res3)->getNTriples();
        $this->assertStringContainsString('<http://example.com/s1> <http://example.com/p3> "o3"', $actual);
    }

    public function testGetTurtle()
    {
        $actual = $this->obj->setIndex($this->res3)->setPrefix('ex', 'http://example.com/')->getTurtle();
        $this->assertStringContainsString('<http://example.com/s1> ex:p3 "o3"', $actual);
    }

    public function testGetRDFXML()
    {
        $actual = $this->obj->setIndex($this->res3)->getRDFXML();
        $this->assertStringContainsString('<rdf:Description rdf:about="http://example.com/s1">', $actual);
    }

    public function testGetJSON()
    {
        $actual = $this->obj->setIndex($this->res3)->getJSON();
        $this->assertStringContainsString('{"http:\/\/example.com\/s1":', $actual);
    }
}
