SET FOREIGN_KEY_CHECKS = 0;
CREATE TABLE `OMEKA_TABLE_PREFIX_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_item` (
  `id` int(11) NOT NULL,
  `is_public` tinyint(1) NOT NULL,
  `is_shareable` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_3844995FBF396750` FOREIGN KEY (`id`) REFERENCES `OMEKA_TABLE_PREFIX_resource` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_item_item_set` (
  `item_id` int(11) NOT NULL,
  `item_set_id` int(11) NOT NULL,
  PRIMARY KEY (`item_id`,`item_set_id`),
  KEY `IDX_EA88EE59126F525E` (`item_id`),
  KEY `IDX_EA88EE59960278D7` (`item_set_id`),
  CONSTRAINT `FK_EA88EE59960278D7` FOREIGN KEY (`item_set_id`) REFERENCES `OMEKA_TABLE_PREFIX_item_set` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_EA88EE59126F525E` FOREIGN KEY (`item_id`) REFERENCES `OMEKA_TABLE_PREFIX_item` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_item_set` (
  `id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_76C49232BF396750` FOREIGN KEY (`id`) REFERENCES `OMEKA_TABLE_PREFIX_resource` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_key` (
  `id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `owner_id` int(11) NOT NULL,
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `credential_hash` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `last_ip` varbinary(16) DEFAULT NULL COMMENT '(DC2Type:ip_address)',
  `last_accessed` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D76D40D87E3C61F9` (`owner_id`),
  CONSTRAINT `FK_D76D40D87E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `OMEKA_TABLE_PREFIX_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_media` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci COMMENT '(DC2Type:json_array)',
  `is_public` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_6BD08FB693CB796C` (`file_id`),
  KEY `IDX_6BD08FB6126F525E` (`item_id`),
  CONSTRAINT `FK_6BD08FB6BF396750` FOREIGN KEY (`id`) REFERENCES `OMEKA_TABLE_PREFIX_resource` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6BD08FB6126F525E` FOREIGN KEY (`item_id`) REFERENCES `OMEKA_TABLE_PREFIX_item` (`id`),
  CONSTRAINT `FK_6BD08FB693CB796C` FOREIGN KEY (`file_id`) REFERENCES `OMEKA_TABLE_PREFIX_file` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_migration` (
  `version` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_module` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_option` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:json_array)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_property` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `vocabulary_id` int(11) NOT NULL,
  `local_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `label` varchar(255) COLLATE utf8_bin NOT NULL,
  `comment` longtext COLLATE utf8_bin,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vocabulary_local_name` (`vocabulary_id`,`local_name`),
  KEY `IDX_FC37D0027E3C61F9` (`owner_id`),
  KEY `IDX_FC37D002AD0E05F6` (`vocabulary_id`),
  CONSTRAINT `FK_FC37D002AD0E05F6` FOREIGN KEY (`vocabulary_id`) REFERENCES `OMEKA_TABLE_PREFIX_vocabulary` (`id`),
  CONSTRAINT `FK_FC37D0027E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `OMEKA_TABLE_PREFIX_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
CREATE TABLE `OMEKA_TABLE_PREFIX_property_assignment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_assignment_set_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `alternate_label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alternate_comment` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_B6672E54D1169F72` (`property_assignment_set_id`),
  KEY `IDX_B6672E54549213EC` (`property_id`),
  CONSTRAINT `FK_B6672E54549213EC` FOREIGN KEY (`property_id`) REFERENCES `OMEKA_TABLE_PREFIX_property` (`id`),
  CONSTRAINT `FK_B6672E54D1169F72` FOREIGN KEY (`property_assignment_set_id`) REFERENCES `OMEKA_TABLE_PREFIX_property_assignment_set` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_property_assignment_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_class_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resource_class_label` (`resource_class_id`,`label`),
  KEY `IDX_D57D24E0448CC1BD` (`resource_class_id`),
  KEY `IDX_D57D24E07E3C61F9` (`owner_id`),
  CONSTRAINT `FK_D57D24E07E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `OMEKA_TABLE_PREFIX_user` (`id`),
  CONSTRAINT `FK_D57D24E0448CC1BD` FOREIGN KEY (`resource_class_id`) REFERENCES `OMEKA_TABLE_PREFIX_resource_class` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `resource_class_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  `resource_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CB5438CA7E3C61F9` (`owner_id`),
  KEY `IDX_CB5438CA448CC1BD` (`resource_class_id`),
  CONSTRAINT `FK_CB5438CA448CC1BD` FOREIGN KEY (`resource_class_id`) REFERENCES `OMEKA_TABLE_PREFIX_resource_class` (`id`),
  CONSTRAINT `FK_CB5438CA7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `OMEKA_TABLE_PREFIX_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_resource_class` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `vocabulary_id` int(11) NOT NULL,
  `local_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `label` varchar(255) COLLATE utf8_bin NOT NULL,
  `comment` longtext COLLATE utf8_bin,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vocabulary_local_name` (`vocabulary_id`,`local_name`),
  KEY `IDX_9FC4DAC27E3C61F9` (`owner_id`),
  KEY `IDX_9FC4DAC2AD0E05F6` (`vocabulary_id`),
  CONSTRAINT `FK_9FC4DAC2AD0E05F6` FOREIGN KEY (`vocabulary_id`) REFERENCES `OMEKA_TABLE_PREFIX_vocabulary` (`id`),
  CONSTRAINT `FK_9FC4DAC27E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `OMEKA_TABLE_PREFIX_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
CREATE TABLE `OMEKA_TABLE_PREFIX_site` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `theme` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `navigation` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:json_array)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_4E1CB5A5989D9B62` (`slug`),
  KEY `IDX_4E1CB5A57E3C61F9` (`owner_id`),
  CONSTRAINT `FK_4E1CB5A57E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `OMEKA_TABLE_PREFIX_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_site_block_attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `block_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `media_id` int(11) DEFAULT NULL,
  `caption` longtext COLLATE utf8_unicode_ci NOT NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_81FB5321E9ED820C` (`block_id`),
  KEY `IDX_81FB5321126F525E` (`item_id`),
  KEY `IDX_81FB5321EA9FDD75` (`media_id`),
  KEY `block_order` (`block_id`,`order`),
  CONSTRAINT `FK_81FB5321EA9FDD75` FOREIGN KEY (`media_id`) REFERENCES `OMEKA_TABLE_PREFIX_media` (`id`),
  CONSTRAINT `FK_81FB5321126F525E` FOREIGN KEY (`item_id`) REFERENCES `OMEKA_TABLE_PREFIX_item` (`id`),
  CONSTRAINT `FK_81FB5321E9ED820C` FOREIGN KEY (`block_id`) REFERENCES `OMEKA_TABLE_PREFIX_site_page_block` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_site_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assigner_id` int(11) DEFAULT NULL,
  `site_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AB93C3D494221246` (`assigner_id`),
  KEY `IDX_AB93C3D4F6BD1646` (`site_id`),
  KEY `IDX_AB93C3D4126F525E` (`item_id`),
  CONSTRAINT `FK_AB93C3D4126F525E` FOREIGN KEY (`item_id`) REFERENCES `OMEKA_TABLE_PREFIX_item` (`id`),
  CONSTRAINT `FK_AB93C3D494221246` FOREIGN KEY (`assigner_id`) REFERENCES `OMEKA_TABLE_PREFIX_user` (`id`),
  CONSTRAINT `FK_AB93C3D4F6BD1646` FOREIGN KEY (`site_id`) REFERENCES `OMEKA_TABLE_PREFIX_site` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_site_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_slug` (`site_id`,`slug`),
  KEY `IDX_A08250EAF6BD1646` (`site_id`),
  CONSTRAINT `FK_A08250EAF6BD1646` FOREIGN KEY (`site_id`) REFERENCES `OMEKA_TABLE_PREFIX_site` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_site_page_block` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `layout` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `data` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:json_array)',
  `order` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_18C7AF41C4663E4` (`page_id`),
  KEY `page_order` (`page_id`,`order`),
  CONSTRAINT `FK_18C7AF41C4663E4` FOREIGN KEY (`page_id`) REFERENCES `OMEKA_TABLE_PREFIX_site_page` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_user` (
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
CREATE TABLE `OMEKA_TABLE_PREFIX_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `value_resource_id` int(11) DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8_unicode_ci,
  `value_transformed` longtext COLLATE utf8_unicode_ci,
  `lang` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_html` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1C8B768E89329D25` (`resource_id`),
  KEY `IDX_1C8B768E549213EC` (`property_id`),
  KEY `IDX_1C8B768E4BC72506` (`value_resource_id`),
  CONSTRAINT `FK_1C8B768E4BC72506` FOREIGN KEY (`value_resource_id`) REFERENCES `OMEKA_TABLE_PREFIX_resource` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1C8B768E549213EC` FOREIGN KEY (`property_id`) REFERENCES `OMEKA_TABLE_PREFIX_property` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1C8B768E89329D25` FOREIGN KEY (`resource_id`) REFERENCES `OMEKA_TABLE_PREFIX_resource` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `OMEKA_TABLE_PREFIX_vocabulary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `namespace_uri` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `prefix` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `comment` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2FC6BA369B267FDF` (`namespace_uri`),
  UNIQUE KEY `UNIQ_2FC6BA3693B1868E` (`prefix`),
  KEY `IDX_2FC6BA367E3C61F9` (`owner_id`),
  CONSTRAINT `FK_2FC6BA367E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `OMEKA_TABLE_PREFIX_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;
