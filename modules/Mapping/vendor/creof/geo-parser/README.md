# creof/geo-parser

[![Code Climate](https://codeclimate.com/github/creof/geo-parser/badges/gpa.svg)](https://codeclimate.com/github/creof/geo-parser)
[![Test Coverage](https://codeclimate.com/github/creof/geo-parser/badges/coverage.svg)](https://codeclimate.com/github/creof/geo-parser/coverage)
[![Build Status](https://travis-ci.org/creof/geo-parser.svg)](https://travis-ci.org/creof/geo-parser)

Lexer and parser library for geometric and geographic point string values.

## Usage

There are two use patterns for the parser. The value to be parsed can be passed into the constructor, then parse()
called on the returned ```Parser``` object:

```php
$input  = '79°56′55″W, 40°26′46″N';

$parser = new Parser($input);

$value = $parser->parse();
```

If many values need to be parsed, a single ```Parser``` instance can be used:

```php
$input1 = '56.242 E';
$input2 = '40:26:46 S';

$parser = new Parser();

$value1 = $parser->parse($input1);
$value2 = $parser->parse($input2);
```

## Supported Formats

Both single values and pairs are supported. Some samples of supported formats are below, though not every possible iteration may be explicitly specified:

1. Simple single signed values
 * 40
 * -40
 * -8.543
 * +132
 * +77.2

2. Simple single signed values with degree symbol
 * 40°
 * -40°
 * -5.234°
 * +43°
 * +38.43°

3. Single unsigned values with or without degree symbol, and cardinal direction
 * 40° N
 * 40 S
 * 56.242 E

4. Single values of signed integer degrees with degree symbol, and decimal minutes with apostrophe
 * 40° 26.222'
 * -65° 32.22'
 * +165° 52.22'

5. Single values of unsigned integer degrees with degree symbol, decimal minutes with apostrophe, and cardinal direction
 * 40° 26.222' E
 * 65° 32.22' S

6. Single values of signed integer degrees with degree symbol, integer minutes with apostrophe, and optional integer or decimal seconds with quote
 * 40° 26' 46"
 * -79° 58' 56"
 * 93° 19' 25.8"
 * +120° 19' 25.8"

6. Single values of signed integer degrees with colon symbol, integer minutes, and optional colon and integer or decimal seconds
 * +40:26:46
 * -79:58:56
 * 93:19:25.8

7. Single values of unsigned integer degrees with degree symbol, integer minutes with apostrophe, optional integer or decimal seconds with quote, and cardinal direction
 * 40° 26' 46" S
 * 99° 58' 56" W
 * 44° 58' 53.9" N

7. Single values of unsigned integer degrees with colon symbol, integer minutes with, optional colon and integer or decimal seconds, and cardinal direction
 * 40:26:46 S
 * 99:58:56 W
 * 44:58:53.9 N

8. Two of any one format separated by whitespace

9. Two of any one format separated by a comma

## Return

The parser will return a integer/float or an array containing a pair of these values.

## Exceptions

The ```Lexer``` and ```Parser``` will throw exceptions implementing interface ```CrEOF\Geo\String\Exception\ExceptionInterface```.
