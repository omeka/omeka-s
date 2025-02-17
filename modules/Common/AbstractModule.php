<?php declare(strict_types=1);
/*
 * Copyright Daniel Berthereau, 2018-2024
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Common;

/**
 * This class allows to manage all methods that should run only once and that
 * are generic to all modules (install and settings).
 *
 * The logic is "config over code": so all settings have just to be set in the
 * main `config/module.config.php` file, inside a key with the lowercase module
 * name,  with sub-keys `config`, `settings`, `site_settings`, `user_settings`
 * and `block_settings`. All the forms have just to be standard Laminas form.
 * Eventual install and uninstall sql can be set in `data/install/` and upgrade
 * code in `data/scripts`.
 *
 * See readme.
 */
abstract class AbstractModule extends \Omeka\Module\AbstractModule
{
    use TraitModule;
}
