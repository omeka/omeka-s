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

namespace LongitudeOne\Spatial\Tests\DBAL\Types;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use LongitudeOne\Spatial\Tests\OrmTestCase;

/**
 * Doctrine schema related tests.
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license https://dlambert.mit-license.org MIT
 *
 * @internal
 * @coversDefaultClass
 */
class SchemaTest extends OrmTestCase
{
    /**
     * Setup the geography type test.
     */
    protected function setUp(): void
    {
        $this->usesEntity(self::GEOMETRY_ENTITY);
        $this->usesEntity(self::POINT_ENTITY);
        $this->usesEntity(self::LINESTRING_ENTITY);
        $this->usesEntity(self::POLYGON_ENTITY);
        $this->usesEntity(self::MULTIPOINT_ENTITY);
        $this->usesEntity(self::MULTIPOLYGON_ENTITY);

        //TODO : Verify what MySQL can do with geography
        if ('postgresql' === $this->getPlatform()->getName()) {
            $this->usesEntity(self::GEOGRAPHY_ENTITY);
            $this->usesEntity(self::GEO_POINT_SRID_ENTITY);
            $this->usesEntity(self::GEO_LINESTRING_ENTITY);
            $this->usesEntity(self::GEO_POLYGON_ENTITY);
        }

        parent::setUp();
    }

    /**
     * Test doctrine type mapping.
     */
    public function testDoctrineTypeMapping()
    {
        $platform = $this->getPlatform();

        foreach ($this->getAllClassMetadata() as $metadata) {
            foreach ($metadata->getFieldNames() as $fieldName) {
                $doctrineType = $metadata->getTypeOfField($fieldName);
                try {
                    $type = Type::getType($doctrineType);
                } catch (Exception $e) {
                    static::fail(sprintf('Unable to get doctrine type %s: %s', $doctrineType, $e->getMessage()));
                }
                $databaseTypes = $type->getMappedDatabaseTypes($platform);

                foreach ($databaseTypes as $databaseType) {
                    try {
                        $typeMapping = $this->getPlatform()->getDoctrineTypeMapping($databaseType);
                        static::assertEquals($doctrineType, $typeMapping);
                    } catch (Exception $e) {
                        static::fail(sprintf('Unable to get doctrine type mapping: %s', $e->getMessage()));
                    }
                }
            }
        }
    }

    /**
     * Test to reverse schema mapping.
     */
    public function testSchemaReverseMapping(): void
    {
        $result = $this->getSchemaTool()->getUpdateSchemaSql($this->getAllClassMetadata(), true);

        static::assertCount(0, $result);
    }

    /**
     * All class metadata getter.
     *
     * @return ClassMetadata[]
     */
    private function getAllClassMetadata(): array
    {
        $metadata = [];

        foreach (array_keys($this->getUsedEntityClasses()) as $entityClass) {
            $metadata[] = $this->getEntityManager()->getClassMetadata($entityClass);
        }

        return $metadata;
    }
}
