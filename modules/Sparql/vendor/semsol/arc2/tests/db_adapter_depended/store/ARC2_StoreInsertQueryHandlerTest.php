<?php

namespace Tests\db_adapter_depended\store;

use Tests\ARC2_TestCase;

class ARC2_StoreInsertQueryHandlerTest extends ARC2_TestCase
{
    protected $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = \ARC2::getStore($this->dbConfig);
        $this->store->drop();
        $this->store->setup();

        $this->fixture = new \ARC2_StoreInsertQueryHandler($this->store->a, $this->store);
    }

    protected function tearDown(): void
    {
        $this->store->closeDBCon();
    }

    /*
     * Tests for __init
     */

    public function testInit()
    {
        $this->fixture = new \ARC2_StoreInsertQueryHandler($this->store->a, $this->store);
        $this->fixture->__init();
        $this->assertEquals($this->store, $this->fixture->store);
    }
}
