<?php
namespace PrestaShopAgenticCommerce\Install;
if (!defined('_PS_VERSION_')) { exit; }
final class DatabaseInstaller
{
    public function install(): bool
    {
        $db=\Db::getInstance();
        $engine=_MYSQL_ENGINE_;
        $meta=_DB_PREFIX_.'agentic_product_meta';
        $evidence=_DB_PREFIX_.'agentic_evidence';
        $sql1="CREATE TABLE IF NOT EXISTS `$meta` (`id_ai_meta` INT UNSIGNED NOT NULL AUTO_INCREMENT,`id_shop` INT UNSIGNED NOT NULL,`id_product` INT UNSIGNED NOT NULL,`id_product_attribute` INT UNSIGNED NOT NULL DEFAULT 0,`category_type` VARCHAR(64) NOT NULL DEFAULT 'general',`sale_unit` VARCHAR(32) NOT NULL DEFAULT 'piece',`verified_specs_json` LONGTEXT NULL,`declared_specs_json` LONGTEXT NULL,`suitability_json` LONGTEXT NULL,`derived_specs_json` LONGTEXT NULL,`source_updated_at` DATETIME NULL,`updated_at` DATETIME NOT NULL,PRIMARY KEY (`id_ai_meta`),UNIQUE KEY `uniq_shop_product_attribute` (`id_shop`,`id_product`,`id_product_attribute`),KEY `idx_product` (`id_product`,`id_product_attribute`),KEY `idx_shop` (`id_shop`)) ENGINE=$engine DEFAULT CHARSET=utf8mb4";
        $sql2="CREATE TABLE IF NOT EXISTS `$evidence` (`id_evidence` INT UNSIGNED NOT NULL AUTO_INCREMENT,`id_shop` INT UNSIGNED NOT NULL,`id_product` INT UNSIGNED NOT NULL,`id_product_attribute` INT UNSIGNED NOT NULL DEFAULT 0,`property_key` VARCHAR(191) NOT NULL,`evidence_class` VARCHAR(16) NOT NULL DEFAULT 'declared',`source_type` VARCHAR(64) NOT NULL,`source_id` VARCHAR(128) NULL,`source_url` VARCHAR(512) NULL,`confidence` DECIMAL(5,4) NOT NULL DEFAULT 1.0000,`evidence_date` DATE NULL,`is_public` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,`status` VARCHAR(16) NOT NULL DEFAULT 'active',`notes` TEXT NULL,`created_at` DATETIME NOT NULL,`updated_at` DATETIME NOT NULL,PRIMARY KEY (`id_evidence`),KEY `idx_lookup` (`id_shop`,`id_product`,`id_product_attribute`,`property_key`),KEY `idx_class_search` (`evidence_class`,`source_type`),KEY `idx_public_status` (`is_public`,`status`)) ENGINE=$engine DEFAULT CHARSET=utf8mb4";
        return $db->execute($sql1) && $db->execute($sql2);
    }
    public function uninstall(): bool { return true; }
}
