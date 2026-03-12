DROP TABLE IF EXISTS `glpi_plugin_ipsuperview_subnets`;
CREATE TABLE `glpi_plugin_ipsuperview_subnets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidr` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `host_count` int unsigned NOT NULL DEFAULT '0',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `is_deleted` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`),
  KEY `name` (`name`),
  KEY `cidr` (`cidr`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginIpsuperviewSubnet',2,2,0);
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginIpsuperviewSubnet',3,3,0);
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginIpsuperviewSubnet',4,4,0);
INSERT INTO `glpi_displaypreferences` VALUES (NULL,'PluginIpsuperviewSubnet',5,5,0);
