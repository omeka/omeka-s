# ARC2

[![Latest Stable Version](https://poser.pugx.org/semsol/arc2/v/stable.svg)](https://packagist.org/packages/semsol/arc2)
[![Total Downloads](https://poser.pugx.org/semsol/arc2/downloads.svg)](https://packagist.org/packages/semsol/arc2)
[![Latest Unstable Version](https://poser.pugx.org/semsol/arc2/v/unstable.svg)](https://packagist.org/packages/semsol/arc2)
[![License](https://poser.pugx.org/semsol/arc2/license.svg)](https://packagist.org/packages/semsol/arc2)

ARC2 is a PHP 8.0+ library for working with RDF.
It also provides a MySQL-based triplestore with SPARQL support.
Older versions of PHP may work, but are not longer tested.

**Test status:**

| Database      | Status                                                                                              |
|---------------|-----------------------------------------------------------------------------------------------------|
| MariaDB 10.5  | ![](https://github.com/semsol/arc2/workflows/MariaDB%2010.5%20Tests/badge.svg)                      |
| MariaDB 10.6  | ![](https://github.com/semsol/arc2/workflows/MariaDB%2010.6%20Tests/badge.svg)                      |
| MariaDB 10.9  | ![](https://github.com/semsol/arc2/workflows/MariaDB%2010.9%20Tests/badge.svg)                      |
| MariaDB 10.10 | ![](https://github.com/semsol/arc2/workflows/MariaDB%2010.10%20Tests/badge.svg)                     |
| MariaDB 10.11 | ![](https://github.com/semsol/arc2/workflows/MariaDB%2010.11%20Tests/badge.svg)                     |
| MySQL 5.5     | ![](https://github.com/semsol/arc2/workflows/MySQL%205.5%20Tests/badge.svg)                         |
| MySQL 5.6     | ![](https://github.com/semsol/arc2/workflows/MySQL%205.6%20Tests/badge.svg)                         |
| MySQL 5.7     | ![](https://github.com/semsol/arc2/workflows/MySQL%205.7%20Tests/badge.svg)                         |
| MySQL 8.0     | ![](https://github.com/semsol/arc2/workflows/MySQL%208.0%20Tests/badge.svg) - incomplete! see below |
| MySQL 8.1     | ![](https://github.com/semsol/arc2/workflows/MySQL%208.1%20Tests/badge.svg) - incomplete! see below |

## Documentation

For the documentation, see the [Wiki](https://github.com/semsol/arc2/wiki#core-documentation). To quickly get started, see the [Getting started guide](https://github.com/semsol/arc2/wiki/Getting-started-with-ARC2).

## Installation

Requires **PHP 8.0**+.

Package available on [Composer](https://packagist.org/packages/semsol/arc2).

You should use Composer for installation:

```bash
composer require semsol/arc2:^3
```

Further information about Composer usage can be found [here](https://getcomposer.org/doc/01-basic-usage.md#autoloading), for instance about autoloading ARC2 classes.

## RDF triple store

### SPARQL support

Please have a look into [SPARQL-support.md](doc/SPARQL-support.md) to see which SPARQL 1.0/1.1 features are currently supported.

### Known database problems

#### MySQL 8.0+

The following error occurs when using a REGEX function inside a SELECT query.

>  General error: 3995 Character set 'utf8mb3_unicode_ci' cannot be used in conjunction with 'binary' in call to regexp_like.

## Internal information for developers

Please have a look [here](doc/developer.md) to find information about maintaining and extending ARC2 as well as our docker setup for local development.
