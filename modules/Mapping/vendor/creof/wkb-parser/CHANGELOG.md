# Change Log
All notable changes to this project will be documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added

### Changed

### Removed

## [2.3.0] - 2016-05-22
### Added
- Tests for empty geometry objects.
- getCurrentPosition() and getLastPosition methods in Reader to get position in byte stream.
- Support for OCG 1.2 encoding of 3D and 4D geometry.
- Method getBadTypeInTypeMessage() in Parser to generate helpful and descriptive exception message.
- Badge and configuration for Coveralls.

### Changed
- NaN coordinates are not returned in point value array, empty point value now array().
- Reader::readDouble() now deprecated and calls Reader::readFloat().
- Reader::readDoubles() now deprecated and calls Reader::readFloats().
- unpack() errors are now caught in unpackInput() and a library exception thrown.
- Inner types (points in multipoint, etc.) are now checked for same dimensions of parent object.
- The search for 'x' in hex values beginning with 'x' or '0x' is now case-insensitive.
- Supported encoding and input formats added to documentation.
- References for encodings added to documentation.
- Lots of additional test data and cases, and cleanup.
- Library exceptions now caught in readGeometry() and rethrown appending Reader position in message.
- All thrown exceptions now have a message.
- Now a single return for all code paths in Parser::getMachineByteOrder().
- Tweaked tests and code for 100% coverage.
- Updated travis config for coveralls.

## [2.2.0] - 2016-05-03
### Added
- Added Tests namespace to Composer PSR-0 dev autoload.
- Added 'dimension' key to returned array containing object dimensions (Z, M, or ZM).
- Reader::getMachineByteOrder method to detect running platform endianness.

### Changed
- Parser property with Reader instance no longer static.
- Replaced sprintf function call in Reader::unpackInput() with string concatenation.
- Updated PHPUnit config to be compliant with XSD.
- Updated PHPUnit config to use Composer autoload.
- Updated documentation with new usage pattern.
- Type name in returned array now contains only base type without dimensions (Z, M, and ZM).
- Reader::readDouble() now checks running platform endianness before byte-swapping values instead of assuming little-endian.

### Removed
- Removed now unused TestInit

## [2.1.0] - 2016-02-18
### Added
- Reader load() method to allow reusing a Reader instance.
- Parser parse() method to allow reusing a Parser instance.
- 3DZ, 3DM, and 4DZM support for all types.
- Support for CIRCULARSTRING type.
- Support for COMPOUNDCURVE type.
- Support for CURVEPOLYGON type.
- Support for MULTICURVE type.
- Support for MULTISURFACE type.
- Preliminary support for POLYHEDRALSURFACE type.

### Changed
- Major refactoring of Parser class.
- Nested types are now checked for permitted types (ie. only Points in MultiPoint, etc.)

## [2.0.0] - 2015-11-18
### Added
- Change base namespace to CrEOF\Geo\WKB to avoid class collision with other CrEOF packages.

## [1.0.1] - 2015-11-17
### Changed
- Replaced if/else statement with ternary operator in parseInput method of Reader.

## [1.0.0] - 2015-11-16
### Added
- Initial release.
