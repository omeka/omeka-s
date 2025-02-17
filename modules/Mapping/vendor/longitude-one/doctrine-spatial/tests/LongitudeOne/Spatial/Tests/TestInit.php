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

use Composer\Autoload\ClassLoader;

require __DIR__.'/../../../../vendor/autoload.php';

error_reporting(E_ALL | E_STRICT);

$loader = new ClassLoader();
$loader->add('LongitudeOne\Spatial\Tests', __DIR__.'/../../..');
$loader->add('Doctrine\Tests', __DIR__.'/../../../../vendor/doctrine/orm/tests');
$loader->register();
