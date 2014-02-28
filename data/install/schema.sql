SET FOREIGN_KEY_CHECKS = 0;
CREATE TABLE `DBPREFIX_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `DBPREFIX_item` (
  `id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_3844995FBF396750` FOREIGN KEY (`id`) REFERENCES `DBPREFIX_resource` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `DBPREFIX_item_set` (
  `id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_76C49232BF396750` FOREIGN KEY (`id`) REFERENCES `DBPREFIX_resource` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `DBPREFIX_media` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_6BD08FB693CB796C` (`file_id`),
  KEY `IDX_6BD08FB6126F525E` (`item_id`),
  CONSTRAINT `FK_6BD08FB6BF396750` FOREIGN KEY (`id`) REFERENCES `DBPREFIX_resource` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6BD08FB6126F525E` FOREIGN KEY (`item_id`) REFERENCES `DBPREFIX_item` (`id`),
  CONSTRAINT `FK_6BD08FB693CB796C` FOREIGN KEY (`file_id`) REFERENCES `DBPREFIX_file` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `DBPREFIX_migration` (
  `version` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `DBPREFIX_module` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `DBPREFIX_property` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `vocabulary_id` int(11) DEFAULT NULL,
  `local_name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `label` varchar(255) COLLATE utf8_bin NOT NULL,
  `comment` longtext COLLATE utf8_bin,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vocabulary_local_name` (`vocabulary_id`,`local_name`),
  KEY `IDX_FC37D0027E3C61F9` (`owner_id`),
  KEY `IDX_FC37D002AD0E05F6` (`vocabulary_id`),
  CONSTRAINT `FK_FC37D002AD0E05F6` FOREIGN KEY (`vocabulary_id`) REFERENCES `DBPREFIX_vocabulary` (`id`),
  CONSTRAINT `FK_FC37D0027E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `DBPREFIX_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
CREATE TABLE `DBPREFIX_property_override` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_class_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `alternate_label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alternate_comment` longtext COLLATE utf8_unicode_ci,
  `visible` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8BBC5FA1448CC1BD` (`resource_class_id`),
  KEY `IDX_8BBC5FA1549213EC` (`property_id`),
  CONSTRAINT `FK_8BBC5FA1549213EC` FOREIGN KEY (`property_id`) REFERENCES `DBPREFIX_property` (`id`),
  CONSTRAINT `FK_8BBC5FA1448CC1BD` FOREIGN KEY (`resource_class_id`) REFERENCES `DBPREFIX_resource_class` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `DBPREFIX_resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `resource_class_id` int(11) DEFAULT NULL,
  `resource_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CB5438CA7E3C61F9` (`owner_id`),
  KEY `IDX_CB5438CA448CC1BD` (`resource_class_id`),
  CONSTRAINT `FK_CB5438CA448CC1BD` FOREIGN KEY (`resource_class_id`) REFERENCES `DBPREFIX_resource_class` (`id`),
  CONSTRAINT `FK_CB5438CA7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `DBPREFIX_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `DBPREFIX_resource_class` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `vocabulary_id` int(11) DEFAULT NULL,
  `local_name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `label` varchar(255) COLLATE utf8_bin NOT NULL,
  `comment` longtext COLLATE utf8_bin,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vocabulary_local_name` (`vocabulary_id`,`local_name`),
  KEY `IDX_9FC4DAC27E3C61F9` (`owner_id`),
  KEY `IDX_9FC4DAC2AD0E05F6` (`vocabulary_id`),
  CONSTRAINT `FK_9FC4DAC2AD0E05F6` FOREIGN KEY (`vocabulary_id`) REFERENCES `DBPREFIX_vocabulary` (`id`),
  CONSTRAINT `FK_9FC4DAC27E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `DBPREFIX_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
CREATE TABLE `DBPREFIX_site` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `DBPREFIX_site_resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assigner_id` int(11) DEFAULT NULL,
  `site_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_375833B094221246` (`assigner_id`),
  KEY `IDX_375833B0F6BD1646` (`site_id`),
  KEY `IDX_375833B089329D25` (`resource_id`),
  CONSTRAINT `FK_375833B089329D25` FOREIGN KEY (`resource_id`) REFERENCES `DBPREFIX_resource` (`id`),
  CONSTRAINT `FK_375833B094221246` FOREIGN KEY (`assigner_id`) REFERENCES `DBPREFIX_user` (`id`),
  CONSTRAINT `FK_375833B0F6BD1646` FOREIGN KEY (`site_id`) REFERENCES `DBPREFIX_site` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `DBPREFIX_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `password_hash` varchar(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `role` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_AACC6A08F85E0677` (`username`),
  UNIQUE KEY `UNIQ_AACC6A08E7927C74` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `DBPREFIX_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `resource_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `value_resource_id` int(11) DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8_unicode_ci,
  `value_transformed` longtext COLLATE utf8_unicode_ci,
  `lang` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_html` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1C8B768E7E3C61F9` (`owner_id`),
  KEY `IDX_1C8B768E89329D25` (`resource_id`),
  KEY `IDX_1C8B768E549213EC` (`property_id`),
  KEY `IDX_1C8B768E4BC72506` (`value_resource_id`),
  CONSTRAINT `FK_1C8B768E4BC72506` FOREIGN KEY (`value_resource_id`) REFERENCES `DBPREFIX_resource` (`id`),
  CONSTRAINT `FK_1C8B768E549213EC` FOREIGN KEY (`property_id`) REFERENCES `DBPREFIX_property` (`id`),
  CONSTRAINT `FK_1C8B768E7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `DBPREFIX_user` (`id`),
  CONSTRAINT `FK_1C8B768E89329D25` FOREIGN KEY (`resource_id`) REFERENCES `DBPREFIX_resource` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `DBPREFIX_vocabulary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `namespace_uri` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `comment` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2FC6BA369B267FDF` (`namespace_uri`),
  KEY `IDX_2FC6BA367E3C61F9` (`owner_id`),
  CONSTRAINT `FK_2FC6BA367E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `DBPREFIX_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;
