<?php

namespace Tests\db_adapter_depended\store\query;

use Tests\ARC2_TestCase;

/**
 * Tests for query method - focus on how the system reacts, when errors occur.
 */
class ErrorHandlingInQueriesTest extends ARC2_TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = \ARC2::getStore($this->dbConfig);
        $this->fixture->drop();
        $this->fixture->setup();
    }

    protected function tearDown(): void
    {
        $this->fixture->closeDBCon();
    }

    /**
     * What if a result variable is not used in query.
     */
    public function testResultVariableNotUsedInQuery()
    {
        $res = $this->fixture->query('
            SELECT ?not_used_in_query ?s WHERE {
                ?s ?p ?o .
            }
        ');

        $this->assertEquals(
            [
                'query_type' => 'select',
                'result' => [
                    'variables' => [
                        'not_used_in_query', 's',
                    ],
                    'rows' => [
                    ],
                ],
                'query_time' => $res['query_time'],
            ],
            $res
        );

        $this->assertTrue(2 <= \count($this->fixture->errors));
    }
}
