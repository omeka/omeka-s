# Change Log
All notable changes to this project will be documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added

### Changed

### Removed

## [2.2.1] - 2019-08-07
### Changed
- Fix compatibility with doctrine/lexer 1.1.0. PR [#18](https://github.com/creof/geo-parser/pull/18) by [bcremer](https://github.com/bcremer).

## [2.2.0] - 2019-08-06
### Added
- Support for PHP 7.1 thru 7.3

### Removed
- Support for PHP earlier than 7.1 

## [2.1.0] - 2016-05-03
### Added
- Support for numbers in scientific notation.

### Changed
- Parser constructor no longer requires a value, enabling instance reuse.
- Lexer constructor no longer requires a value, enabling instance reuse.
- Tests now use Composer autoload.
- PHPUnit config XML now conforms to XSD.
- Documentation updated with new usage pattern.
- Move non-value comparison into if statement in Lexer::getType().
- Test case and test data cleanup.

### Removed
- TestInit no longer needed

## [2.0.0] - 2015-11-18
### Added
- Change base namespace to CrEOF\Geo\String to avoid class collision with other CrEOF packages.

## [1.0.1] - 2015-11-17
### Added
- Exclude fingerprint for Code Climate fixme engine to ignore "Stub TODO.md file." in changelog.
### Changed
- Removed code for unused conditions in Parser error methods.
- Removed case for token T_PERIOD in getType method of Lexer, it's not used in Parser.

## [1.0.0] - 2015-11-11
### Added
- Change log file to chronicle changes.
- Dependency on SPL extension to composer.json since the package exceptions extend them.
- Stub TODO.md file.
- CONTRIBUTING.md file with guidelines.
- Travis CI config
- Code Climate config
- Add support for unicode prime and double prime.
- Tests for uncovered parser branches.
### Changed
- Use string compare instead of regex for cardinal direction.
- Remove unneeded deps for phpmd and phpcs - Code Climate with handle this.
- Match seconds symbol with symbol().
- Change property names in parser to more accurately indicate what they're for.

