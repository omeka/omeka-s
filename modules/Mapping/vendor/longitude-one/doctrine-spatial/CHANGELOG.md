# Change Log
All notable changes to this project will be documented in this file using the [Keep a CHANGELOG](https://keepachangelog.com/) principles.
This project adheres to [Semantic Versioning](https://semver.org/).

## LongitudeOne/doctrine-spatial [3.0.0-dev]

### TODO
- Support of CircleCI on Github actions (help is welcomed)
- Support for code coverage on Github Actions (help is welcomed)

### Added
- longitude-one/doctrine-spatial replaces CrEOF/doctrine2-spatial
- Support of PHP8.0
- Support for Postgis2.1, PostGis3.0, PostGis3.1
- Namespaces have been updated from CrEOF/Spatial to LongitudeOne/Spatial
- Github actions added for our internal test

### Removed
- Removing support of PHP7.2, PHP7.3
- Removing compatibility with Postgis 2.0. Some spatial functions have been renamed to their 
new names (example: ST_Line_Interpolate_Point has been renamed to ST_Line_Interpolate_Point).
- Removing test on Travis

## CrEOF/doctrine2-spatial [2.0.0] Version 2 - 2020-04-01

## CrEOF/doctrine2-spatial [2.0.0-RC1] Release candidat - 2020-03-26

### Added
- Geometric and geographic entities implements JsonSerialization.

## CrEOF/doctrine2-spatial[2.0.0-RC0] Release candidat - 2020-03-18

### Added
- A new documentation hosted on ReadTheDocs.
- Adding support of PHP7.2, PHP7.3, PHP7.4,
- Needed PHP extension added in composer.json,
- Spatial function implementing the ISO/IEC 13249-3:2016 or [OGC Standard](https://www.ogc.org/standards/sfs) are now stored in the [Standard](lib/LongitudeOne/Spatial/ORM/Query/AST/Functions/Standard) directory.
- Specific spatial function of the PostgreSql server are now store in the [PostgreSql](lib/LongitudeOne/Spatial/ORM/Query/AST/Functions/PostgreSql) directory.
- Specific spatial function of the PostgreSql server are now store in the [MySql](lib/LongitudeOne/Spatial/ORM/Query/AST/Functions/MySql) directory.
- Code coverage is now really at 90 percent. (CreOf code coverage was not valid because of AST functions which contained only properties),
- AST Functions updated to avoid misconfiguration (some properties was missing),
- AST Functions updated to detect which function was not tested,
- A lot of spatial functions,
- A lot of PostgreSql functions,
- Deprecated MySql functions replaced by their new names, 
- Removing deprecations of doctrine2,
- Project forked from creof/doctrine-spatial2.
### Removed
- Removing support of PHP5.*, PHP7.0, PHP7.1

## CrEOF/doctrine2-spatial [1.1.1] - 2020-02-21 
Nota: This version was never published by creof. But the fork begins at this date.
### Added
- Added support for PostgreSql ST_MakeEnvelope function.
### Changed
- Added implementation of getTypeFamily() and getSQLType() to AbstractGeometryType.
- Rename AbstractGeometryType class to AbstractSpatialType.
- Simplify logic in isClosed() method of AbstractLineString.
- Updated copyright year in LICENSE.
### Removed
- Unused imports from a number of classes.

## CrEOF/doctrine2-spatial [1.1] - 2015-12-20
### Added
- Local phpdocs to database platform classes.
- getMappedDatabaseTypes() method to PlatformInterface returning a unique type name used in type mapping.
- Entity and test for setting default SRID in column definition on PostgreSQL/PostGIS.
- Additional parameter to methods in PlatformInterface to pass DBAL type.
- Test class OrmMockTestCase with mocked DBAL connection.
- Test for Geography\Polygon type.
- Test for unsupported platforms.

### Changed
- Moved database platform classes to namespace LongitudeOne\Spatial\DBAL\Platform.
- Define exception messages where thrown in classes.
- Pass entity class names to usesEntity() in tests instead of looking them up in an array.
- Confirm types have not been previously added when setting up tests.
- Geometry and Geography platform classes unified in single class for each database platform.
- Class OrmTest renamed to OrmTestCase.
- Refactor single use methods in AbstractGeometryType into calling method.
- Include all test by default so tests are inadvertently skipped.
- Changed test class names to match filenames.

### Removed
- Static exception messages from package exception classes.
- getTypeFamily() method from PlatformInterface.
- Dependency on ramsey/array_column package.
- Empty test classes.

## CrEOF/doctrine2-spatial [1.0.1] - 2015-12-18
### Added
- Dependency on creof/geo-parser.
- Dependency on creof/wkt-parser.
- Dependency on creof/wkb-parser.
- Additional spatial functions support for PostgreSQL/PostGIS.

### Changed
- Replace regex in AbstractPoint with parser from creof/geo-parser.
- Use parser from creof/wkt-parser in AbstractPlatform class.
- Use parser from creof/wkb-parser in AbstractPlatform class.

### Removed
- StringLexer and StringParser classes no longer needed.
- BinaryReader, BinaryParser, and Utils classes no longer needed.
- Unused expection methods from InvalidValueException.

## CrEOF/doctrine2-spatial [1.0.0] - 2015-11-09
### Added
- Change log file to chronicle changes.
- Stub TODO.md file.
- CONTRIBUTING.md file with guidelines.
- CrEOF\Spatial\Tests\OrmTest class to remove dependency on doctrine/orm source for tests.
- Travis-CI repo hook and configuration.
- CodeClimate config.
- Test config flag "opt_mark_sql" to execute dummy query with test name before each test.
- Test config flag "opt_use_debug_stack" to use custom stack which logs queries.
- Numerous SQL/DQL functions for both PostgreSQL and MySQL.
- Coveralls config.
- MultiPolygon geometry DBAL type.

### Changed
- Minimum doctrine/orm version now 2.3.
- All ORM tests now extend CrEOF\Spatial\Tests\OrmTest.
- Specifying test platform through @group annotation has been deprecated. Tests now configure supported platforms in setUp(), unsupported tests are skipped.
- Cleaned up existing test classes.
- Replaced rhumsaa/array_column dev package dependency with ramsey/array_column. Prior has been abandoned and is no longer maintained.
- Tests now pass string values to parameters instead of objects to avoid issues with field value conversion.
- Documentation split up into multiple files.
- StringLexer and StringParser now correctly handle values with exponent/scientific notation.

### Removed
- AbstractDualGeometryDQLFunction, AbstractDualGeometryOptionalParameterDQLFunction, AbstractGeometryDQLFunction, AbstractSingleGeometryDQLFunction, AbstractTripleGeometryDQLFunction, and AbstractVariableGeometryDQLFunction classes.
