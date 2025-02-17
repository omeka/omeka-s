# creof/wkb-parser

[![Build Status](https://travis-ci.org/creof/wkb-parser.svg?branch=master)](https://travis-ci.org/creof/wkb-parser)
[![Code Climate](https://codeclimate.com/github/creof/wkb-parser/badges/gpa.svg)](https://codeclimate.com/github/creof/wkb-parser)
[![Test Coverage](https://codeclimate.com/github/creof/wkb-parser/badges/coverage.svg)](https://codeclimate.com/github/creof/wkb-parser/coverage)
[![Coverage Status](https://coveralls.io/repos/github/creof/wkb-parser/badge.svg?branch=master)](https://coveralls.io/github/creof/wkb-parser?branch=master)

Parser library for 2D, 3D, and 4D Open Geospatial Consortium (OGC) WKB or PostGIS EWKB spatial object data.

## Usage

There are two use patterns for the parser. The value to be parsed can be passed into the constructor, then parse()
called on the returned ```Parser``` object:

```php
$parser = new Parser($input);

$value = $parser->parse();
```

If many values need to be parsed, a single ```Parser``` instance can be used:

```php
$parser = new Parser();

$value1 = $parser->parse($input1);
$value2 = $parser->parse($input2);
```

### Input value

#### Encoding

The parser currently supports 3 WKB encodings:

 - OGC v1.1
 - OGC v1.2
 - PostGIS EWKB

#### Format

The parser supports a number of input formats:

 - Binary string (as returned from database or ```pack('H*', $hexString)```)
 - Bare hexadecimal text string (```'01010000003D0AD7A3.....'```)
 - Hexadecimal test string prepended with ```x```, ```X```, ```0x```, or ```0X``` (```'0x01010000003D0AD7A3.....'```, etc.)

## Return

The parser will return an array with the keys ```type```, ```value```, ```srid```, and ```dimension```.
- ```type``` string, the uppercase spatial object type (```POINT```, ```LINESTRING```, etc.) without any dimension.
- ```value``` array, contains integer or float values for points, nested arrays containing these based on spatial object type, or empty array for EMPTY geometry.
- ```srid``` integer, the SRID if present in EWKB value, ```null``` otherwise.
- ```dimension``` string, will contain ```Z```, ```M```, or ```ZM``` for the respective 3D and 4D objects, ```null``` otherwise.

## Exceptions

The ```Reader``` and ```Parser``` will throw exceptions implementing interface ```CrEOF\Geo\WKB\Exception\ExceptionInterface```.

## References
 - PostGIS EWKB - https://github.com/postgis/postgis/blob/svn-trunk/doc/ZMSgeoms.txt
 - OGC Simple Feature Access, Part 1 - http://www.opengeospatial.org/standards/sfa
 - OGC Simple Feature Access, Part 2 - http://www.opengeospatial.org/standards/sfs

