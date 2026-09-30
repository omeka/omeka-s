PRAGMA foreign_keys = OFF;
CREATE TABLE `api_key` (
  `id` varchar(32) NOT NULL,
  `owner_id` INTEGER NOT NULL,
  `label` varchar(255) NOT NULL,
  `credential_hash` varchar(60) NOT NULL,
  `last_ip` BLOB DEFAULT NULL,
  `last_accessed` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_C912ED9D7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`)
);
CREATE INDEX `IDX_C912ED9D7E3C61F9` ON `api_key` (`owner_id`);
CREATE TABLE `asset` (
  `id` INTEGER NOT NULL,
  `owner_id` INTEGER DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `media_type` varchar(255) NOT NULL,
  `storage_id` varchar(190) NOT NULL,
  `extension` varchar(255) DEFAULT NULL,
  `alt_text` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE (`storage_id`),
  CONSTRAINT `FK_2AF5A5C7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
);
CREATE INDEX `IDX_2AF5A5C7E3C61F9` ON `asset` (`owner_id`);
CREATE TABLE `fulltext_search` (
  `id` INTEGER NOT NULL,
  `resource` varchar(190) NOT NULL,
  `owner_id` INTEGER DEFAULT NULL,
  `is_public` INTEGER NOT NULL,
  `title` TEXT,
  `text` TEXT,
  PRIMARY KEY (`id`,`resource`),
  CONSTRAINT `FK_AA31FE4A7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
);
CREATE INDEX `IDX_AA31FE4A7E3C61F9` ON `fulltext_search` (`owner_id`);
CREATE TABLE `item` (
  `id` INTEGER NOT NULL,
  `primary_media_id` INTEGER DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_1F1B251EBF396750` FOREIGN KEY (`id`) REFERENCES `resource` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1F1B251ECBE0B084` FOREIGN KEY (`primary_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
);
CREATE INDEX `IDX_1F1B251ECBE0B084` ON `item` (`primary_media_id`);
CREATE TABLE `item_item_set` (
  `item_id` INTEGER NOT NULL,
  `item_set_id` INTEGER NOT NULL,
  PRIMARY KEY (`item_id`,`item_set_id`),
  CONSTRAINT `FK_6D0C9625126F525E` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6D0C9625960278D7` FOREIGN KEY (`item_set_id`) REFERENCES `item_set` (`id`) ON DELETE CASCADE
);
CREATE INDEX `IDX_6D0C9625126F525E` ON `item_item_set` (`item_id`);
CREATE INDEX `IDX_6D0C9625960278D7` ON `item_item_set` (`item_set_id`);
CREATE TABLE `item_set` (
  `id` INTEGER NOT NULL,
  `is_open` INTEGER NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_1015EEEBF396750` FOREIGN KEY (`id`) REFERENCES `resource` (`id`) ON DELETE CASCADE
);
CREATE TABLE `item_site` (
  `item_id` INTEGER NOT NULL,
  `site_id` INTEGER NOT NULL,
  PRIMARY KEY (`item_id`,`site_id`),
  CONSTRAINT `FK_A1734D1F126F525E` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_A1734D1FF6BD1646` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`) ON DELETE CASCADE
);
CREATE INDEX `IDX_A1734D1F126F525E` ON `item_site` (`item_id`);
CREATE INDEX `IDX_A1734D1FF6BD1646` ON `item_site` (`site_id`);
CREATE TABLE `job` (
  `id` INTEGER NOT NULL,
  `owner_id` INTEGER DEFAULT NULL,
  `pid` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `class` varchar(255) NOT NULL,
  `args` TEXT,
  `log` TEXT,
  `started` datetime NOT NULL,
  `ended` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_FBD8E0F87E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
);
CREATE INDEX `IDX_FBD8E0F87E3C61F9` ON `job` (`owner_id`);
CREATE TABLE `media` (
  `id` INTEGER NOT NULL,
  `item_id` INTEGER NOT NULL,
  `ingester` varchar(255) NOT NULL,
  `renderer` varchar(255) NOT NULL,
  `data` TEXT,
  `source` TEXT,
  `media_type` varchar(190) DEFAULT NULL,
  `storage_id` varchar(190) DEFAULT NULL,
  `extension` varchar(255) DEFAULT NULL,
  `sha256` char(64) DEFAULT NULL,
  `size` INTEGER DEFAULT NULL,
  `has_original` INTEGER NOT NULL,
  `has_thumbnails` INTEGER NOT NULL,
  `position` INTEGER DEFAULT NULL,
  `lang` varchar(190) DEFAULT NULL,
  `alt_text` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE (`storage_id`),
  CONSTRAINT `FK_6A2CA10C126F525E` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`),
  CONSTRAINT `FK_6A2CA10CBF396750` FOREIGN KEY (`id`) REFERENCES `resource` (`id`) ON DELETE CASCADE
);
CREATE INDEX `IDX_6A2CA10C126F525E` ON `media` (`item_id`);
CREATE INDEX `item_position` ON `media` (`item_id`, `position`);
CREATE INDEX `media_type` ON `media` (`media_type`);
CREATE TABLE `migration` (
  `version` varchar(16) NOT NULL,
  PRIMARY KEY (`version`)
);
CREATE TABLE `module` (
  `id` varchar(190) NOT NULL,
  `is_active` INTEGER NOT NULL,
  `version` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
);
CREATE TABLE `password_creation` (
  `id` varchar(32) NOT NULL,
  `user_id` INTEGER NOT NULL,
  `created` datetime NOT NULL,
  `activate` INTEGER NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`user_id`),
  CONSTRAINT `FK_C77917B4A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
);
CREATE TABLE `property` (
  `id` INTEGER NOT NULL,
  `owner_id` INTEGER DEFAULT NULL,
  `vocabulary_id` INTEGER NOT NULL,
  `local_name` varchar(190) NOT NULL,
  `label` varchar(255) NOT NULL,
  `comment` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE (`vocabulary_id`, `local_name`),
  CONSTRAINT `FK_8BF21CDE7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_8BF21CDEAD0E05F6` FOREIGN KEY (`vocabulary_id`) REFERENCES `vocabulary` (`id`)
);
CREATE INDEX `IDX_8BF21CDE7E3C61F9` ON `property` (`owner_id`);
CREATE INDEX `IDX_8BF21CDEAD0E05F6` ON `property` (`vocabulary_id`);
CREATE TABLE `resource` (
  `id` INTEGER NOT NULL,
  `owner_id` INTEGER DEFAULT NULL,
  `resource_class_id` INTEGER DEFAULT NULL,
  `resource_template_id` INTEGER DEFAULT NULL,
  `thumbnail_id` INTEGER DEFAULT NULL,
  `title` TEXT,
  `is_public` INTEGER NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  `resource_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_BC91F41616131EA` FOREIGN KEY (`resource_template_id`) REFERENCES `resource_template` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_BC91F416448CC1BD` FOREIGN KEY (`resource_class_id`) REFERENCES `resource_class` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_BC91F4167E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_BC91F416FDFF2E92` FOREIGN KEY (`thumbnail_id`) REFERENCES `asset` (`id`) ON DELETE SET NULL
);
CREATE INDEX `IDX_BC91F4167E3C61F9` ON `resource` (`owner_id`);
CREATE INDEX `IDX_BC91F416448CC1BD` ON `resource` (`resource_class_id`);
CREATE INDEX `IDX_BC91F41616131EA` ON `resource` (`resource_template_id`);
CREATE INDEX `IDX_BC91F416FDFF2E92` ON `resource` (`thumbnail_id`);
CREATE INDEX `title` ON `resource` (`title`);
CREATE INDEX `is_public_resource` ON `resource` (`is_public`);
CREATE TABLE `resource_class` (
  `id` INTEGER NOT NULL,
  `owner_id` INTEGER DEFAULT NULL,
  `vocabulary_id` INTEGER NOT NULL,
  `local_name` varchar(190) NOT NULL,
  `label` varchar(255) NOT NULL,
  `comment` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE (`vocabulary_id`, `local_name`),
  CONSTRAINT `FK_C6F063AD7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_C6F063ADAD0E05F6` FOREIGN KEY (`vocabulary_id`) REFERENCES `vocabulary` (`id`)
);
CREATE INDEX `IDX_C6F063AD7E3C61F9` ON `resource_class` (`owner_id`);
CREATE INDEX `IDX_C6F063ADAD0E05F6` ON `resource_class` (`vocabulary_id`);
CREATE TABLE `resource_template` (
  `id` INTEGER NOT NULL,
  `owner_id` INTEGER DEFAULT NULL,
  `resource_class_id` INTEGER DEFAULT NULL,
  `title_property_id` INTEGER DEFAULT NULL,
  `description_property_id` INTEGER DEFAULT NULL,
  `label` varchar(190) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`label`),
  CONSTRAINT `FK_39ECD52E448CC1BD` FOREIGN KEY (`resource_class_id`) REFERENCES `resource_class` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_39ECD52E724734A3` FOREIGN KEY (`title_property_id`) REFERENCES `property` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_39ECD52E7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_39ECD52EB84E0D1D` FOREIGN KEY (`description_property_id`) REFERENCES `property` (`id`) ON DELETE SET NULL
);
CREATE INDEX `IDX_39ECD52E7E3C61F9` ON `resource_template` (`owner_id`);
CREATE INDEX `IDX_39ECD52E448CC1BD` ON `resource_template` (`resource_class_id`);
CREATE INDEX `IDX_39ECD52E724734A3` ON `resource_template` (`title_property_id`);
CREATE INDEX `IDX_39ECD52EB84E0D1D` ON `resource_template` (`description_property_id`);
CREATE TABLE `resource_template_property` (
  `id` INTEGER NOT NULL,
  `resource_template_id` INTEGER NOT NULL,
  `property_id` INTEGER NOT NULL,
  `alternate_label` varchar(255) DEFAULT NULL,
  `alternate_comment` TEXT,
  `position` INTEGER DEFAULT NULL,
  `data_type` TEXT,
  `is_required` INTEGER NOT NULL,
  `is_private` INTEGER NOT NULL,
  `default_lang` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`resource_template_id`, `property_id`),
  CONSTRAINT `FK_4689E2F116131EA` FOREIGN KEY (`resource_template_id`) REFERENCES `resource_template` (`id`),
  CONSTRAINT `FK_4689E2F1549213EC` FOREIGN KEY (`property_id`) REFERENCES `property` (`id`) ON DELETE CASCADE
);
CREATE INDEX `IDX_4689E2F116131EA` ON `resource_template_property` (`resource_template_id`);
CREATE INDEX `IDX_4689E2F1549213EC` ON `resource_template_property` (`property_id`);
CREATE TABLE `session` (
  `id` varchar(190) NOT NULL,
  `data` BLOB NOT NULL,
  `modified` INTEGER NOT NULL,
  PRIMARY KEY (`id`)
);
CREATE TABLE `setting` (
  `id` varchar(190) NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`id`)
);
CREATE TABLE `site` (
  `id` INTEGER NOT NULL,
  `thumbnail_id` INTEGER DEFAULT NULL,
  `homepage_id` INTEGER DEFAULT NULL,
  `owner_id` INTEGER DEFAULT NULL,
  `slug` varchar(190) NOT NULL,
  `theme` varchar(190) NOT NULL,
  `title` varchar(190) NOT NULL,
  `summary` TEXT,
  `navigation` TEXT NOT NULL,
  `item_pool` TEXT NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  `is_public` INTEGER NOT NULL,
  `assign_new_items` INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE (`slug`),
  UNIQUE (`homepage_id`),
  CONSTRAINT `FK_694309E4571EDDA` FOREIGN KEY (`homepage_id`) REFERENCES `site_page` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_694309E47E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_694309E4FDFF2E92` FOREIGN KEY (`thumbnail_id`) REFERENCES `asset` (`id`) ON DELETE SET NULL
);
CREATE INDEX `IDX_694309E4FDFF2E92` ON `site` (`thumbnail_id`);
CREATE INDEX `IDX_694309E47E3C61F9` ON `site` (`owner_id`);
CREATE TABLE `site_block_attachment` (
  `id` INTEGER NOT NULL,
  `block_id` INTEGER NOT NULL,
  `item_id` INTEGER DEFAULT NULL,
  `media_id` INTEGER DEFAULT NULL,
  `caption` TEXT NOT NULL,
  `position` INTEGER NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_236473FE126F525E` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_236473FEE9ED820C` FOREIGN KEY (`block_id`) REFERENCES `site_page_block` (`id`),
  CONSTRAINT `FK_236473FEEA9FDD75` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
);
CREATE INDEX `IDX_236473FEE9ED820C` ON `site_block_attachment` (`block_id`);
CREATE INDEX `IDX_236473FE126F525E` ON `site_block_attachment` (`item_id`);
CREATE INDEX `IDX_236473FEEA9FDD75` ON `site_block_attachment` (`media_id`);
CREATE INDEX `block_position` ON `site_block_attachment` (`block_id`, `position`);
CREATE TABLE `site_item_set` (
  `id` INTEGER NOT NULL,
  `site_id` INTEGER NOT NULL,
  `item_set_id` INTEGER NOT NULL,
  `position` INTEGER DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`site_id`, `item_set_id`),
  CONSTRAINT `FK_D4CE134960278D7` FOREIGN KEY (`item_set_id`) REFERENCES `item_set` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D4CE134F6BD1646` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`) ON DELETE CASCADE
);
CREATE INDEX `IDX_D4CE134F6BD1646` ON `site_item_set` (`site_id`);
CREATE INDEX `IDX_D4CE134960278D7` ON `site_item_set` (`item_set_id`);
CREATE INDEX `position` ON `site_item_set` (`position`);
CREATE TABLE `site_page` (
  `id` INTEGER NOT NULL,
  `site_id` INTEGER NOT NULL,
  `slug` varchar(190) NOT NULL,
  `title` varchar(190) NOT NULL,
  `is_public` INTEGER NOT NULL,
  `layout` varchar(255) DEFAULT NULL,
  `layout_data` TEXT,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`site_id`, `slug`),
  CONSTRAINT `FK_2F900BD9F6BD1646` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`)
);
CREATE INDEX `is_public_site_page` ON `site_page` (`is_public`);
CREATE INDEX `IDX_2F900BD9F6BD1646` ON `site_page` (`site_id`);
CREATE TABLE `site_page_block` (
  `id` INTEGER NOT NULL,
  `page_id` INTEGER NOT NULL,
  `layout` varchar(80) NOT NULL,
  `data` TEXT NOT NULL,
  `layout_data` TEXT,
  `position` INTEGER NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_C593E731C4663E4` FOREIGN KEY (`page_id`) REFERENCES `site_page` (`id`)
);
CREATE INDEX `IDX_C593E731C4663E4` ON `site_page_block` (`page_id`);
CREATE INDEX `page_position` ON `site_page_block` (`page_id`, `position`);
CREATE TABLE `site_permission` (
  `id` INTEGER NOT NULL,
  `site_id` INTEGER NOT NULL,
  `user_id` INTEGER NOT NULL,
  `role` varchar(80) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`site_id`, `user_id`),
  CONSTRAINT `FK_C0401D6FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_C0401D6FF6BD1646` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`) ON DELETE CASCADE
);
CREATE INDEX `IDX_C0401D6FF6BD1646` ON `site_permission` (`site_id`);
CREATE INDEX `IDX_C0401D6FA76ED395` ON `site_permission` (`user_id`);
CREATE TABLE `site_setting` (
  `id` varchar(190) NOT NULL,
  `site_id` INTEGER NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`id`,`site_id`),
  CONSTRAINT `FK_64D05A53F6BD1646` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`) ON DELETE CASCADE
);
CREATE INDEX `IDX_64D05A53F6BD1646` ON `site_setting` (`site_id`);
CREATE TABLE `user` (
  `id` INTEGER NOT NULL,
  `email` varchar(190) NOT NULL,
  `name` varchar(190) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  `password_hash` varchar(60) DEFAULT NULL,
  `role` varchar(190) NOT NULL,
  `is_active` INTEGER NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`email`)
);
CREATE TABLE `user_setting` (
  `id` varchar(190) NOT NULL,
  `user_id` INTEGER NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`id`,`user_id`),
  CONSTRAINT `FK_C779A692A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
);
CREATE INDEX `IDX_C779A692A76ED395` ON `user_setting` (`user_id`);
CREATE TABLE `value` (
  `id` INTEGER NOT NULL,
  `resource_id` INTEGER NOT NULL,
  `property_id` INTEGER NOT NULL,
  `value_resource_id` INTEGER DEFAULT NULL,
  `value_annotation_id` INTEGER DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `lang` varchar(255) DEFAULT NULL,
  `value` TEXT,
  `uri` TEXT,
  `is_public` INTEGER NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE (`value_annotation_id`),
  CONSTRAINT `FK_1D7758344BC72506` FOREIGN KEY (`value_resource_id`) REFERENCES `resource` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1D775834549213EC` FOREIGN KEY (`property_id`) REFERENCES `property` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_1D77583489329D25` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`id`),
  CONSTRAINT `FK_1D7758349B66727E` FOREIGN KEY (`value_annotation_id`) REFERENCES `value_annotation` (`id`) ON DELETE SET NULL
);
CREATE INDEX `IDX_1D77583489329D25` ON `value` (`resource_id`);
CREATE INDEX `IDX_1D775834549213EC` ON `value` (`property_id`);
CREATE INDEX `IDX_1D7758344BC72506` ON `value` (`value_resource_id`);
CREATE INDEX `value_value` ON `value` (`value`);
CREATE INDEX `uri` ON `value` (`uri`);
CREATE INDEX `is_public_value` ON `value` (`is_public`);
CREATE TABLE `value_annotation` (
  `id` INTEGER NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_C03BA4EBF396750` FOREIGN KEY (`id`) REFERENCES `resource` (`id`) ON DELETE CASCADE
);
CREATE TABLE `vocabulary` (
  `id` INTEGER NOT NULL,
  `owner_id` INTEGER DEFAULT NULL,
  `namespace_uri` varchar(190) NOT NULL,
  `prefix` varchar(190) NOT NULL,
  `label` varchar(255) NOT NULL,
  `comment` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE (`namespace_uri`),
  UNIQUE (`prefix`),
  CONSTRAINT `FK_9099C97B7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
);
CREATE INDEX `IDX_9099C97B7E3C61F9` ON `vocabulary` (`owner_id`);
PRAGMA foreign_keys = ON;
