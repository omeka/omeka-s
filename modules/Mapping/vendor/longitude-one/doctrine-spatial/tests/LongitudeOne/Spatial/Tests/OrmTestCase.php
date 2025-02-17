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

namespace LongitudeOne\Spatial\Tests;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Persistence\Mapping\MappingException;
use InvalidArgumentException;
use LongitudeOne\Spatial\DBAL\Types\Geography\LineStringType as GeographyLineStringType;
use LongitudeOne\Spatial\DBAL\Types\Geography\PointType as GeographyPointType;
use LongitudeOne\Spatial\DBAL\Types\Geography\PolygonType as GeographyPolygonType;
use LongitudeOne\Spatial\DBAL\Types\GeographyType;
use LongitudeOne\Spatial\DBAL\Types\Geometry\LineStringType;
use LongitudeOne\Spatial\DBAL\Types\Geometry\MultiLineStringType;
use LongitudeOne\Spatial\DBAL\Types\Geometry\MultiPointType;
use LongitudeOne\Spatial\DBAL\Types\Geometry\MultiPolygonType;
use LongitudeOne\Spatial\DBAL\Types\Geometry\PointType;
use LongitudeOne\Spatial\DBAL\Types\Geometry\PolygonType;
use LongitudeOne\Spatial\DBAL\Types\GeometryType;
use LongitudeOne\Spatial\Exception\UnsupportedPlatformException;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpBuffer;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpBufferStrategy;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpDistance;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpGeometryType as MySqlGeometryType;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpLineString;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpMbrContains;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpMbrDisjoint;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpMbrEquals;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpMbrIntersects;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpMbrOverlaps;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpMbrTouches;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpMbrWithin;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\MySql\SpPoint;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpAsGeoJson;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpAzimuth;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpClosestPoint;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpCollect;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpContainsProperly;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpCoveredBy;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpCovers;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpDistanceSphere;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpDWithin;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpExpand;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpGeogFromText;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpGeographyFromText;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpGeometryType as PgSqlGeometryType;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpGeomFromEwkt;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpLineCrossingDirection;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpLineInterpolatePoint;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpLineLocatePoint;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpLineSubstring;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpMakeBox2D;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpMakeEnvelope;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpMakeLine;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpMakePoint;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpNPoints;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpScale;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpSimplify;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpSnapToGrid;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpSplit;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpSummary;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpTransform;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\PostgreSql\SpTranslate;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StArea;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StAsBinary;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StAsText;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StBoundary;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StBuffer;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StCentroid;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StContains;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StConvexHull;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StCrosses;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StDifference;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StDimension;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StDisjoint;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StDistance;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StEndPoint;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StEnvelope;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StEquals;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StExteriorRing;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StGeometryN;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StGeometryType;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StGeomFromText;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StGeomFromWkb;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StInteriorRingN;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StIntersection;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StIntersects;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StIsClosed;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StIsEmpty;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StIsRing;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StIsSimple;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StLength;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StLineStringFromWkb;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StMLineFromWkb;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StMPointFromWkb;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StMPolyFromWkb;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StNumGeometries;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StNumInteriorRing;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StNumPoints;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StOverlaps;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StPerimeter;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StPoint;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StPointFromWkb;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StPointN;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StPointOnSurface;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StPolyFromWkb;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StRelate;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StSetSRID;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StSrid;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StStartPoint;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StSymDifference;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StTouches;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StUnion;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StWithin;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StX;
use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StY;
use LongitudeOne\Spatial\Tests\Fixtures\GeographyEntity;
use LongitudeOne\Spatial\Tests\Fixtures\GeoLineStringEntity;
use LongitudeOne\Spatial\Tests\Fixtures\GeometryEntity;
use LongitudeOne\Spatial\Tests\Fixtures\GeoPointSridEntity;
use LongitudeOne\Spatial\Tests\Fixtures\GeoPolygonEntity;
use LongitudeOne\Spatial\Tests\Fixtures\LineStringEntity;
use LongitudeOne\Spatial\Tests\Fixtures\MultiLineStringEntity;
use LongitudeOne\Spatial\Tests\Fixtures\MultiPointEntity;
use LongitudeOne\Spatial\Tests\Fixtures\MultiPolygonEntity;
use LongitudeOne\Spatial\Tests\Fixtures\NoHintGeometryEntity;
use LongitudeOne\Spatial\Tests\Fixtures\PointEntity;
use LongitudeOne\Spatial\Tests\Fixtures\PolygonEntity;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Throwable;

// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
// phpcs miss the Exception

/**
 * Abstract ORM test class.
 */
abstract class OrmTestCase extends TestCase
{
    //Fixtures and entities
    public const GEO_LINESTRING_ENTITY = GeoLineStringEntity::class;
    public const GEO_POINT_SRID_ENTITY = GeoPointSridEntity::class;
    public const GEO_POLYGON_ENTITY = GeoPolygonEntity::class;
    public const GEOGRAPHY_ENTITY = GeographyEntity::class;
    public const GEOMETRY_ENTITY = GeometryEntity::class;
    public const LINESTRING_ENTITY = LineStringEntity::class;
    public const MULTILINESTRING_ENTITY = MultiLineStringEntity::class;
    public const MULTIPOINT_ENTITY = MultiPointEntity::class;
    public const MULTIPOLYGON_ENTITY = MultiPolygonEntity::class;
    public const NO_HINT_GEOMETRY_ENTITY = NoHintGeometryEntity::class;
    public const POINT_ENTITY = PointEntity::class;
    public const POLYGON_ENTITY = PolygonEntity::class;

    /**
     * @var bool[]
     */
    protected static $addedTypes = [];

    /**
     * @var Connection
     */
    protected static $connection;

    /**
     * @var bool[]
     */
    protected static $createdEntities = [];

    /**
     * @var array[]
     */
    protected static $entities = [
        GeometryEntity::class => [
            'types' => ['geometry'],
            'table' => 'GeometryEntity',
        ],
        NoHintGeometryEntity::class => [
            'types' => ['geometry'],
            'table' => 'NoHintGeometryEntity',
        ],
        PointEntity::class => [
            'types' => ['point'],
            'table' => 'PointEntity',
        ],
        LineStringEntity::class => [
            'types' => ['linestring'],
            'table' => 'LineStringEntity',
        ],
        PolygonEntity::class => [
            'types' => ['polygon'],
            'table' => 'PolygonEntity',
        ],
        MultiPointEntity::class => [
            'types' => ['multipoint'],
            'table' => 'MultiPointEntity',
        ],
        MultiLineStringEntity::class => [
            'types' => ['multilinestring'],
            'table' => 'MultiLineStringEntity',
        ],
        MultiPolygonEntity::class => [
            'types' => ['multipolygon'],
            'table' => 'MultiPolygonEntity',
        ],
        GeographyEntity::class => [
            'types' => ['geography'],
            'table' => 'GeographyEntity',
        ],
        GeoPointSridEntity::class => [
            'types' => ['geopoint'],
            'table' => 'GeoPointSridEntity',
        ],
        GeoLineStringEntity::class => [
            'types' => ['geolinestring'],
            'table' => 'GeoLineStringEntity',
        ],
        GeoPolygonEntity::class => [
            'types' => ['geopolygon'],
            'table' => 'GeoPolygonEntity',
        ],
    ];

    /**
     * @var string[]
     */
    protected static $types = [
        'geometry' => GeometryType::class,
        'point' => PointType::class,
        'linestring' => LineStringType::class,
        'polygon' => PolygonType::class,
        'multipoint' => MultiPointType::class,
        'multilinestring' => MultiLineStringType::class,
        'multipolygon' => MultiPolygonType::class,
        'geography' => GeographyType::class,
        'geopoint' => GeographyPointType::class,
        'geolinestring' => GeographyLineStringType::class,
        'geopolygon' => GeographyPolygonType::class,
    ];

    protected EntityManagerInterface $entityManager;

    /**
     * @var bool[]
     */
    protected array $supportedPlatforms = [];

    /**
     * @var bool[]
     */
    protected array $usedEntities = [];

    /**
     * @var bool[]
     */
    protected array $usedTypes = [];

    private SchemaTool $schemaTool;

    private DebugStack $sqlLoggerStack;

    /**
     * Setup connection before class creation.
     */
    public static function setUpBeforeClass(): void
    {
        try {
            static::$connection = static::getConnection();
        } catch (UnsupportedPlatformException|Exception $e) {
            static::fail(sprintf('Unable to establish connection in %s: %s', __FILE__, $e->getMessage()));
        }
    }

    /**
     * Creates a connection to the test database, if there is none yet, and creates the necessary tables.
     */
    protected function setUp(): void
    {
        try {
            if (!isset($this->supportedPlatforms[$this->getPlatform()->getName()])) {
                static::markTestSkipped(sprintf(
                    'No support for platform %s in test class %s.',
                    $this->getPlatform()->getName(),
                    get_class($this)
                ));
            }

            $this->entityManager = $this->getEntityManager();
            $this->schemaTool = $this->getSchemaTool();

            if ($GLOBALS['opt_mark_sql']) {
                $query = sprintf('SELECT 1 /*%s*//*%s*/', get_class($this), $this->getName());
                static::getConnection()->executeQuery($query);
            }

            $this->sqlLoggerStack->enabled = $GLOBALS['opt_use_debug_stack'];

            $this->setUpTypes();
            $this->setUpEntities();
            $this->setUpFunctions();
        } catch (UnsupportedPlatformException|Exception $e) {
            static::fail(sprintf('Unable to setup test in %s: %s', __FILE__, $e->getMessage()));
        }
    }

    /**
     * Teardown fixtures.
     */
    protected function tearDown(): void
    {
        $this->sqlLoggerStack->enabled = false;

        try {
            foreach (array_keys($this->usedEntities) as $entityName) {
                static::getConnection()->executeStatement(sprintf(
                    'DELETE FROM %s',
                    static::$entities[$entityName]['table']
                ));
            }

            $this->getEntityManager()->clear();
        } catch (Exception|MappingException|UnsupportedPlatformException $e) {
            static::fail(sprintf('Unable to clear table after test: %s', $e->getMessage()));
        }
    }

    /**
     * Assert empty geometry.
     * MySQL5 does not return the standard answer, but this bug was solved in MySQL8.
     * So test for an empty geometry is a little more complex than to compare two strings.
     *
     * @param mixed                 $value    Value to test
     * @param AbstractPlatform|null $platform the platform
     */
    protected static function assertBigPolygon($value, AbstractPlatform $platform = null): void
    {
        switch ($platform->getName()) {
            case 'mysql':
                //MySQL does not respect creation order of points composing a Polygon.
                static::assertSame('POLYGON((0 10,0 0,10 0,10 10,0 10))', $value);
                break;
            case 'postgresl':
            default:
                //Here is the good result.
                // A linestring minus another crossing linestring returns initial linestring splited
                static::assertSame('POLYGON((0 10,10 10,10 0,0 0,0 10))', $value);
        }
    }

    /**
     * Assert empty geometry.
     * MySQL5 does not return the standard answer, but this bug was solved in MySQL8.
     * So test for an empty geometry is a little more complex than to compare two strings.
     *
     * @param mixed                 $value    Value to test
     * @param AbstractPlatform|null $platform the platform
     */
    protected static function assertEmptyGeometry($value, AbstractPlatform $platform = null): void
    {
        $expected = 'EMPTY';
        $method = 'assertStringEndsWith';

        if ($platform instanceof MySQL57Platform && !$platform instanceof MySQL80Platform) {
            //MySQL5 does not return the standard answer
            //This bug was solved in MySQL8
            $expected = 'GEOMETRYCOLLECTION()';
            $method = 'assertSame';
        }

        static::$method($expected, $value);
    }

    /**
     * Return common connection parameters.
     *
     * @return array
     */
    protected static function getCommonConnectionParameters()
    {
        $connectionParams = [
            'driver' => $GLOBALS['db_type'],
            'user' => $GLOBALS['db_username'],
            'password' => null,
            'host' => $GLOBALS['db_host'],
            'dbname' => null,
            'port' => $GLOBALS['db_port'],
        ];

        if (isset($GLOBALS['db_server'])) {
            $connectionParams['server'] = $GLOBALS['db_server'];
        }

        if (!empty($GLOBALS['db_password'])) {
            $connectionParams['password'] = $GLOBALS['db_password'];
        }

        if (isset($GLOBALS['db_unix_socket'])) {
            $connectionParams['unix_socket'] = $GLOBALS['db_unix_socket'];
        }

        if (isset($GLOBALS['db_version'])) {
            $connectionParams['driverOptions']['server_version'] = (string) $GLOBALS['db_version'];
        }

        return $connectionParams;
    }

    /**
     * Establish the connection if it is not already done, then returns it.
     *
     * @throws Exception                    when connection is not successful
     * @throws UnsupportedPlatformException when platform is unsupported
     *
     * @return Connection
     */
    protected static function getConnection()
    {
        if (isset(static::$connection)) {
            return static::$connection;
        }

        $connection = DriverManager::getConnection(static::getConnectionParameters());

        switch ($connection->getDatabasePlatform()->getName()) {
            case 'postgresql':
                $connection->executeStatement('CREATE EXTENSION postgis');
                break;
            case 'mysql':
                break;
            default:
                throw new UnsupportedPlatformException(sprintf(
                    'DBAL platform "%s" is not currently supported.',
                    $connection->getDatabasePlatform()->getName()
                ));
        }

        return $connection;
    }

    /**
     * Return connection parameters.
     *
     * @throws Exception when connection is not successful
     *
     * @return array
     */
    protected static function getConnectionParameters()
    {
        $parameters = static::getCommonConnectionParameters();
        $parameters['dbname'] = $GLOBALS['db_name'];

        $connection = DriverManager::getConnection($parameters);
        $dbName = $connection->getDatabase();

        $connection->close();

        $tmpConnection = DriverManager::getConnection(static::getCommonConnectionParameters());

        $tmpConnection->getSchemaManager()->dropAndCreateDatabase($dbName);
        $tmpConnection->close();

        return $parameters;
    }

    /**
     * Using the SQL Logger Stack this method retrieves the current query count executed in this test.
     *
     * @return int
     */
    protected function getCurrentQueryCount()
    {
        return count($this->sqlLoggerStack->queries);
    }

    /**
     * Return the entity manager.
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (isset($this->entityManager)) {
            return $this->entityManager;
        }

        $this->sqlLoggerStack = new DebugStack();
        $this->sqlLoggerStack->enabled = false;

        try {
            static::getConnection()->getConfiguration()->setSQLLogger($this->sqlLoggerStack);
            $realPaths = [realpath(__DIR__.'/Fixtures')];
            $config = new Configuration();

            $config->setMetadataCache(new ArrayCachePool());
            $config->setProxyDir(__DIR__.'/Proxies');
            $config->setProxyNamespace('LongitudeOne\Spatial\Tests\Proxies');
            //TODO WARNING: a non-expected parameter is provided.
            $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($realPaths, true));

            return EntityManager::create(static::getConnection(), $config);
        } catch (ORMException|Exception|UnsupportedPlatformException $e) {
            static::fail(sprintf('Unable to init the EntityManager: %s', $e->getMessage()));
        }
    }

    /**
     * Get platform.
     *
     * @return AbstractPlatform
     */
    protected function getPlatform(): ?AbstractPlatform
    {
        try {
            return static::getConnection()->getDatabasePlatform();
        } catch (UnsupportedPlatformException|Exception $e) {
            static::fail('Unable to get database platform: '.$e->getMessage());
        }
    }

    /**
     * Return the platform completed by the version number of the server for mysql.
     */
    protected function getPlatformAndVersion(): string
    {
        if ($this->getPlatform() instanceof MySQL80Platform) {
            return 'mysql8';
        }

        if ($this->getPlatform() instanceof MySQL57Platform) {
            return 'mysql5';
        }

        return $this->getPlatform()->getName();
    }

    /**
     * Return the schema tool.
     *
     * @return SchemaTool
     */
    protected function getSchemaTool()
    {
        if (isset($this->schemaTool)) {
            return $this->schemaTool;
        }

        return new SchemaTool($this->getEntityManager());
    }

    /**
     * Return the static created entity classes.
     *
     * @return array
     */
    protected function getUsedEntityClasses()
    {
        return static::$createdEntities;
    }

    /**
     * On not successful test.
     *
     * @param Throwable $throwable the exception
     *
     * @throws InvalidArgumentException the formatted exception when sql logger is on
     * @throws Throwable                the exception provided as parameter
     */
    protected function onNotSuccessfulTest(Throwable $throwable): void
    {
        if (!$GLOBALS['opt_use_debug_stack'] || $throwable instanceof AssertionFailedError) {
            throw $throwable;
        }

        if (isset($this->sqlLoggerStack->queries) && count($this->sqlLoggerStack->queries)) {
            $queries = '';
            $count = count($this->sqlLoggerStack->queries) - 1;
            $max = max(count($this->sqlLoggerStack->queries) - 25, 0);

            for ($i = $count; $i > $max && isset($this->sqlLoggerStack->queries[$i]); --$i) {
                $query = $this->sqlLoggerStack->queries[$i];
                $params = array_map(function ($param) {
                    if (is_object($param)) {
                        return get_class($param);
                    }

                    return sprintf("'%s'", $param);
                }, $query['params'] ?: []);

                $queries .= sprintf(
                    "%2d. SQL: '%s' Params: %s\n",
                    $i,
                    $query['sql'],
                    implode(', ', $params)
                );
            }

            $trace = $throwable->getTrace();
            $traceMsg = '';

            foreach ($trace as $part) {
                if (isset($part['file'])) {
                    if (false !== mb_strpos($part['file'], 'PHPUnit/')) {
                        // Beginning with PHPUnit files we don't print the trace anymore.
                        break;
                    }

                    $traceMsg .= sprintf("%s:%s\n", $part['file'], $part['line']);
                }
            }

            $message = sprintf("[%s] %s\n\n", get_class($throwable), $throwable->getMessage());
            $message .= sprintf("With queries:\n%s\nTrace:\n%s", $queries, $traceMsg);

            throw new InvalidArgumentException($message, $throwable->getCode(), $throwable);
        }

        throw $throwable;
    }

    /**
     * Create entities used by tests.
     */
    protected function setUpEntities()
    {
        $classes = [];

        foreach (array_keys($this->usedEntities) as $entityClass) {
            if (!isset(static::$createdEntities[$entityClass])) {
                static::$createdEntities[$entityClass] = true;
                $classes[] = $this->getEntityManager()->getClassMetadata($entityClass);
            }
        }

        if ($classes) {
            try {
                $this->getSchemaTool()->createSchema($classes);
            } catch (ToolsException $e) {
                static::fail('Unable to create schema: '.$e->getMessage());
            }
        }
    }

    /**
     * Setup DQL functions.
     */
    protected function setUpFunctions()
    {
        $configuration = $this->getEntityManager()->getConfiguration();

        $this->addStandardFunctions($configuration);

        if ('postgresql' === $this->getPlatformAndVersion()) {
            //Specific functions of PostgreSQL server
            $this->addSpecificPostgreSqlFunctions($configuration);
        }

        //This test does not work when we compare to 'mysql' (on Travis only)
        if ('postgresql' !== $this->getPlatform()->getName()) {
            $this->addSpecificMySqlFunctions($configuration);
        }
    }

    /**
     * Add types used by test to DBAL.
     */
    protected function setUpTypes()
    {
        foreach (array_keys($this->usedTypes) as $typeName) {
            if (!isset(static::$addedTypes[$typeName]) && !Type::hasType($typeName)) {
                try {
                    Type::addType($typeName, static::$types[$typeName]);
                } catch (Exception $e) {
                    static::fail(sprintf('Unable to add type %s: %s', $typeName, $e->getMessage()));
                }

                try {
                    $type = Type::getType($typeName);

                    // Since doctrineTypeComments may already be initialized check if added type requires comment
                    $platform = $this->getPlatform();
                    if ($type->requiresSQLCommentHint($platform) && !$platform->isCommentedDoctrineType($type)) {
                        $this->getPlatform()->markDoctrineTypeCommented(Type::getType($typeName));
                    }
                } catch (Exception $e) {
                    static::fail(sprintf('Unable to get type %s: %s', $typeName, $e->getMessage()));
                }

                static::$addedTypes[$typeName] = true;
            }
        }
    }

    /**
     * Set the supported platforms.
     *
     * @param string $platform the platform to support
     */
    protected function supportsPlatform(string $platform): void
    {
        $this->supportedPlatforms[$platform] = true;
    }

    /**
     * Declare the used entity class to initialized them (and delete its content before the test).
     *
     * @param string $entityClass the entity class
     */
    protected function usesEntity(string $entityClass): void
    {
        $this->usedEntities[$entityClass] = true;

        foreach (static::$entities[$entityClass]['types'] as $type) {
            $this->usesType($type);
        }
    }

    /**
     * Set the type used.
     *
     * @param string $typeName the type name
     */
    protected function usesType(string $typeName): void
    {
        $this->usedTypes[$typeName] = true;
    }

    /**
     * Complete configuration with MySQL spatial functions.
     *
     * @param Configuration $configuration the current configuration
     */
    private function addSpecificMySqlFunctions(Configuration $configuration): void
    {
        $configuration->addCustomNumericFunction('Mysql_Distance', SpDistance::class);
        $configuration->addCustomNumericFunction('Mysql_Buffer', SpBuffer::class);
        $configuration->addCustomNumericFunction('Mysql_BufferStrategy', SpBufferStrategy::class);
        $configuration->addCustomNumericFunction('Mysql_GeometryType', MySqlGeometryType::class);
        $configuration->addCustomNumericFunction('Mysql_LineString', SpLineString::class);
        $configuration->addCustomNumericFunction('Mysql_MBRContains', SpMbrContains::class);
        $configuration->addCustomNumericFunction('Mysql_MBRDisjoint', SpMbrDisjoint::class);
        $configuration->addCustomNumericFunction('Mysql_MBREquals', SpMbrEquals::class);
        $configuration->addCustomNumericFunction('Mysql_MBRIntersects', SpMbrIntersects::class);
        $configuration->addCustomNumericFunction('Mysql_MBROverlaps', SpMbrOverlaps::class);
        $configuration->addCustomNumericFunction('Mysql_MBRTouches', SpMbrTouches::class);
        $configuration->addCustomNumericFunction('Mysql_MBRWithin', SpMbrWithin::class);
        $configuration->addCustomNumericFunction('Mysql_Point', SpPoint::class);
    }

    /**
     * Complete configuration with PostgreSQL spatial functions.
     *
     * @param Configuration $configuration the current configuration
     */
    private function addSpecificPostgreSqlFunctions(Configuration $configuration): void
    {
        $configuration->addCustomStringFunction('PgSql_AsGeoJson', SpAsGeoJson::class);
        $configuration->addCustomStringFunction('PgSql_Azimuth', SpAzimuth::class);
        $configuration->addCustomStringFunction('PgSql_ClosestPoint', SpClosestPoint::class);
        $configuration->addCustomStringFunction('PgSql_Collect', SpCollect::class);
        $configuration->addCustomNumericFunction('PgSql_ContainsProperly', SpContainsProperly::class);
        $configuration->addCustomNumericFunction('PgSql_CoveredBy', SpCoveredBy::class);
        $configuration->addCustomNumericFunction('PgSql_Covers', SpCovers::class);
        $configuration->addCustomNumericFunction('PgSql_Distance_Sphere', SpDistanceSphere::class);
        $configuration->addCustomNumericFunction('PgSql_DWithin', SpDWithin::class);
        $configuration->addCustomNumericFunction('PgSql_Expand', SpExpand::class);
        $configuration->addCustomStringFunction('PgSql_GeogFromText', SpGeogFromText::class);
        $configuration->addCustomStringFunction('PgSql_GeographyFromText', SpGeographyFromText::class);
        $configuration->addCustomNumericFunction('PgSql_GeomFromEwkt', SpGeomFromEwkt::class);
        $configuration->addCustomNumericFunction('PgSql_GeometryType', PgSqlGeometryType::class);
        $configuration->addCustomNumericFunction('PgSql_LineCrossingDirection', SpLineCrossingDirection::class);
        $configuration->addCustomNumericFunction('PgSql_LineSubstring', SpLineSubstring::class);
        $configuration->addCustomNumericFunction('PgSql_LineLocatePoint', SpLineLocatePoint::class);
        $configuration->addCustomStringFunction('PgSql_LineInterpolatePoint', SpLineInterpolatePoint::class);
        $configuration->addCustomStringFunction('PgSql_MakeEnvelope', SpMakeEnvelope::class);
        $configuration->addCustomStringFunction('PgSql_MakeBox2D', SpMakeBox2D::class);
        $configuration->addCustomStringFunction('PgSql_MakeLine', SpMakeLine::class);
        $configuration->addCustomStringFunction('PgSql_MakePoint', SpMakePoint::class);
        $configuration->addCustomNumericFunction('PgSql_NPoints', SpNPoints::class);
        $configuration->addCustomNumericFunction('PgSql_Scale', SpScale::class);
        $configuration->addCustomNumericFunction('PgSql_Simplify', SpSimplify::class);
        $configuration->addCustomNumericFunction('PgSql_Split', SpSplit::class);
        $configuration->addCustomStringFunction('PgSql_SnapToGrid', SpSnapToGrid::class);
        $configuration->addCustomStringFunction('PgSql_Summary', SpSummary::class);
        $configuration->addCustomNumericFunction('PgSql_Transform', SpTransform::class);
        $configuration->addCustomNumericFunction('PgSql_Translate', SpTranslate::class);
    }

    /**
     * Add all standard functions.
     *
     * @param Configuration $configuration the configuration to update
     */
    private function addStandardFunctions(Configuration $configuration): void
    {
        //Generic spatial functions described in OGC Standard
        $configuration->addCustomNumericFunction('ST_Area', StArea::class);
        $configuration->addCustomStringFunction('ST_AsBinary', StAsBinary::class);
        $configuration->addCustomStringFunction('ST_AsText', StAsText::class);
        $configuration->addCustomStringFunction('ST_Boundary', StBoundary::class);
        $configuration->addCustomNumericFunction('ST_Buffer', StBuffer::class);
        $configuration->addCustomStringFunction('ST_Centroid', StCentroid::class);
        $configuration->addCustomNumericFunction('ST_Contains', StContains::class);
        $configuration->addCustomStringFunction('ST_ConvexHull', StConvexHull::class);
        $configuration->addCustomNumericFunction('ST_Crosses', StCrosses::class);
        $configuration->addCustomStringFunction('ST_Difference', StDifference::class);
        $configuration->addCustomNumericFunction('ST_Dimension', StDimension::class);
        $configuration->addCustomNumericFunction('ST_Disjoint', StDisjoint::class);
        $configuration->addCustomNumericFunction('ST_Distance', StDistance::class);
        $configuration->addCustomNumericFunction('ST_Equals', StEquals::class);
        $configuration->addCustomNumericFunction('ST_Intersects', StIntersects::class);
        $configuration->addCustomStringFunction('ST_Intersection', StIntersection::class);
        $configuration->addCustomNumericFunction('ST_IsClosed', StIsClosed::class);
        $configuration->addCustomNumericFunction('ST_IsEmpty', StIsEmpty::class);
        $configuration->addCustomNumericFunction('ST_IsRing', StIsRing::class);
        $configuration->addCustomNumericFunction('ST_IsSimple', StIsSimple::class);
        $configuration->addCustomStringFunction('ST_EndPoint', StEndPoint::class);
        $configuration->addCustomStringFunction('ST_Envelope', StEnvelope::class);
        $configuration->addCustomStringFunction('ST_ExteriorRing', StExteriorRing::class);
        $configuration->addCustomStringFunction('ST_GeometryN', StGeometryN::class);
        $configuration->addCustomStringFunction('ST_GeometryType', StGeometryType::class);
        $configuration->addCustomStringFunction('ST_GeomFromWkb', StGeomFromWkb::class);
        $configuration->addCustomStringFunction('ST_GeomFromText', StGeomFromText::class);
        $configuration->addCustomStringFunction('ST_InteriorRingN', StInteriorRingN::class);
        $configuration->addCustomNumericFunction('ST_Length', StLength::class);
        $configuration->addCustomStringFunction('ST_LineStringFromWkb', StLineStringFromWkb::class);
        $configuration->addCustomStringFunction('ST_MPointFromWkb', StMPointFromWkb::class);
        $configuration->addCustomStringFunction('ST_MLineFromWkb', StMLineFromWkb::class);
        $configuration->addCustomStringFunction('ST_MPolyFromWkb', StMPolyFromWkb::class);
        $configuration->addCustomStringFunction('ST_NumInteriorRing', StNumInteriorRing::class);
        $configuration->addCustomStringFunction('ST_NumGeometries', StNumGeometries::class);
        $configuration->addCustomNumericFunction('ST_NumPoints', StNumPoints::class);
        $configuration->addCustomStringFunction('ST_Overlaps', StOverlaps::class);
        $configuration->addCustomNumericFunction('ST_Perimeter', StPerimeter::class);
        $configuration->addCustomStringFunction('ST_Point', StPoint::class);
        $configuration->addCustomStringFunction('ST_PointFromWkb', StPointFromWkb::class);
        $configuration->addCustomStringFunction('ST_PointN', StPointN::class);
        $configuration->addCustomStringFunction('ST_PointOnSurface', StPointOnSurface::class);
        $configuration->addCustomStringFunction('ST_PolyFromWkb', StPolyFromWkb::class);
        $configuration->addCustomStringFunction('ST_Relate', StRelate::class);
        $configuration->addCustomStringFunction('ST_SymDifference', StSymDifference::class);
        $configuration->addCustomNumericFunction('ST_SetSRID', StSetSRID::class);
        $configuration->addCustomNumericFunction('ST_SRID', StSrid::class);
        $configuration->addCustomNumericFunction('ST_StartPoint', StStartPoint::class);
        $configuration->addCustomNumericFunction('ST_Touches', StTouches::class);
        $configuration->addCustomStringFunction('ST_Union', StUnion::class);
        $configuration->addCustomNumericFunction('ST_Within', StWithin::class);
        $configuration->addCustomNumericFunction('ST_X', StX::class);
        $configuration->addCustomNumericFunction('ST_Y', StY::class);
    }
}
