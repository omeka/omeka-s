# Developer information

This document contains information about ARC2 internals which are relevant for maintaining and extending ARC2.

## Run test environment

To run test environment execute:

```bash
vendor/bin/phpunit
```

Tests are split into different groups currently:
* unit
* db_adapter_depended

You can run the `unit` group directly, but you need to set some environment variables for `db_adapter_depended`.

#### config.php

Currently, we use the following standard database credentials to connect with the database:

```php
$dbConfig = array(
    'db_name' => 'arc2_test',
    'db_user' => 'root',
    'db_pwd'  => 'Pass123',
    'db_host' => '127.0.0.1',
);
```

If you have different credentials, copy the `tests/config.php.dist` to `tests/config.php` and set your credentials.

## Editor

Please make sure your editor uses our `.editorconfig` file.

## Docker setup

For ARC2 developers we recommend using our docker setup (see folder `docker`). It provides a pre-configured set of software (for PHP, DBS etc.) and allows quick switches between different software versions.

No matter if one needs a MariaDB 10.3 with PHP 7.2 or a PHP 5.6 with MySQL 5.7.0. If there is a docker container, it runs.

### Start

In your terminal go to `docker` folder and run `make`. It will build and start the docker environment as well as log you in.

### Docker and Travis

We use a very wide range of software-combinations to test ARC2.
Currently, all combinations of supported versions of PHP and database systems (currently MySQL and MariaDB only) are checked.

Using a Docker setup for local development allows to switch the backend very easily.
So, if a test with a certain DBS/PHP version combination fails, its very likely that you can reproduce it locally.
Dont forget to run `composer update` after a switch to make sure appropriate software is used.
