<?php

/*
 * Copyright BibLibre, 2016-2022
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

namespace EADImport;

use Omeka\Module\AbstractModule;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = <<<'SQL'
        CREATE TABLE eadimport_import (id INT AUTO_INCREMENT NOT NULL, job_id INT NOT NULL, site_id INT NOT NULL, name VARCHAR(255) NOT NULL, resource_type VARCHAR(255) NOT NULL, mapping LONGTEXT NOT NULL COMMENT '(DC2Type:json)', UNIQUE INDEX UNIQ_2F1CB77ABE04EA9 (job_id), INDEX IDX_2F1CB77AF6BD1646 (site_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
        CREATE TABLE eadimport_mapping_model (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, mapping LONGTEXT NOT NULL COMMENT '(DC2Type:json)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
        CREATE TABLE eadimport_entity (id INT AUTO_INCREMENT NOT NULL, job_id INT NOT NULL, site_id INT NOT NULL, entity_id INT NOT NULL, resource_type VARCHAR(255) NOT NULL, INDEX IDX_BC7A3D0FBE04EA9 (job_id), INDEX IDX_BC7A3D0FF6BD1646 (site_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
        ALTER TABLE eadimport_import ADD CONSTRAINT FK_2F1CB77ABE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);
        ALTER TABLE eadimport_import ADD CONSTRAINT FK_2F1CB77AF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id);
        ALTER TABLE eadimport_entity ADD CONSTRAINT FK_BC7A3D0FBE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);
        ALTER TABLE eadimport_entity ADD CONSTRAINT FK_BC7A3D0FF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id);
        SQL;

        $sqls = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($sqls as $sql) {
            $connection->exec($sql);
        }
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = <<<'SQL'
        ALTER TABLE eadimport_import DROP FOREIGN KEY FK_2F1CB77ABE04EA9;
        ALTER TABLE eadimport_import DROP FOREIGN KEY FK_2F1CB77AF6BD1646;
        ALTER TABLE eadimport_entity DROP FOREIGN KEY FK_BC7A3D0FBE04EA9;
        ALTER TABLE eadimport_entity DROP FOREIGN KEY FK_BC7A3D0FF6BD1646;

        DROP TABLE IF EXISTS eadimport_import;
        DROP TABLE IF EXISTS eadimport_entity;
        DROP TABLE IF EXISTS eadimport_mapping_model;

        SQL;

        $sqls = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($sqls as $sql) {
            $connection->exec($sql);
        }
    }
}
