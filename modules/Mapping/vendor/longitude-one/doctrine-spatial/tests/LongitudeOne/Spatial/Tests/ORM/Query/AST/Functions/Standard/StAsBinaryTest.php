<?php
/**
 * This file is part of the doctrine spatial extension.
 *
 * PHP 7.4 | 8.0 | 8.1
 *
 * (c) Alexandre Tranchant <alexandre.tranchant@gmail.com> 2017 - 2022
 * (c) Longitude One 2020 - 2022
 * (c) 2015 Derek J. Lambert
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace LongitudeOne\Spatial\Tests\ORM\Query\AST\Functions\Standard;

use LongitudeOne\Spatial\Tests\Helper\LineStringHelperTrait;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * ST_AsBinary DQL function tests.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @author  Alexandre Tranchant <alexandre.tranchant@gmail.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @group dql
 *
 * @internal
 * @coversDefaultClass
 */
class StAsBinaryTest extends OrmTestCase
{
    use LineStringHelperTrait;

    /**
     * Setup the function type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::LINESTRING_ENTITY);
        $this->supportsPlatform('postgresql');
        $this->supportsPlatform('mysql');

        parent::setUp();
    }

    /**
     * Test a DQL containing function to test in the select.
     *
     * @group geometry
     */
    public function testStAsBinary()
    {
        $this->persistStraightLineString();
        $this->persistAngularLineString();
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $query = $this->getEntityManager()->createQuery(
            'SELECT ST_AsBinary(l.lineString) FROM LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity l'
        );
        $result = $query->getResult();

        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $expectedA = '010200000003000000000000000000000000000000000000000000000000000040000000000000004000000000000014400000000000001440';
        $expectedB = '0102000000030000000000000000000840000000000000084000000000000010400000000000002e4000000000000014400000000000003640';
        // phpcs:enable

        switch ($this->getPlatform()->getName()) {
            case 'mysql':
                static::assertEquals(pack('H*', $expectedA), $result[0][1]);
                static::assertEquals(pack('H*', $expectedB), $result[1][1]);
                break;
            case 'postgresql':
            default:
                static::assertEquals($expectedA, bin2hex(stream_get_contents($result[0][1])));
                static::assertEquals($expectedB, bin2hex(stream_get_contents($result[1][1])));
        }
    }
}
