<?php

namespace Tests\db_adapter_depended\store;

use Tests\ARC2_TestCase;

class ARC2_StoreLoadQueryHandlerTest extends ARC2_TestCase
{
    protected $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = \ARC2::getStore($this->dbConfig);
        $this->store->createDBCon();

        // remove all tables
        $this->store->getDBObject()->deleteAllTables();
        $this->store->setUp();

        $this->fixture = new \ARC2_StoreLoadQueryHandler($this->store, $this);
    }

    protected function tearDown(): void
    {
        $this->store->closeDBCon();
    }

    /**
     * Tests behavior, if has to extend columns.
     */
    public function testExtendColumns(): void
    {
        $this->fixture->setStore($this->store);
        $this->fixture->column_type = 'mediumint';
        $this->fixture->max_term_id = 16750001;

        $this->assertEquals(16750001, $this->fixture->getStoredTermID('', '', ''));

        // MySQL
        $table_fields = $this->store->getDBObject()->fetchList('DESCRIBE arc_g2t');
        $this->assertStringContainsString('int', $table_fields[0]['Type']);
        $this->assertStringContainsString('unsigned', $table_fields[0]['Type']);
    }
}
