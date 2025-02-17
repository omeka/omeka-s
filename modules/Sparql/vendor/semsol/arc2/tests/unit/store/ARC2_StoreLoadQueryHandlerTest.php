<?php

namespace Tests\unit\store;

use Tests\ARC2_TestCase;

class ARC2_StoreLoadQueryHandlerTest extends ARC2_TestCase
{
    protected $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = \ARC2::getStore($this->dbConfig);
        $this->store->createDBCon();

        $this->fixture = new \ARC2_StoreLoadQueryHandler($this->store, $this);

        // fresh setup of ARC2
        $this->store->setup();
    }

    protected function tearDown(): void
    {
        $this->store->closeDBCon();
    }

    /*
     * Tests for getOComp
     */

    /**
     * Tests to behavior, if a datetime string was given.
     */
    public function testGetOComp()
    {
        // case with +hourse
        $string = '2009-05-28T18:03:38+09:00';
        $this->assertEquals('2009-05-28T09:03:38Z', $this->fixture->getOComp($string));

        // GMT case
        $string = '2009-05-28T18:03:38GMT';
        $this->assertEquals('2009-05-28T18:03:38Z', $this->fixture->getOComp($string));
    }
}
