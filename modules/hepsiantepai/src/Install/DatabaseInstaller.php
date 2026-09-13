<?php

namespace Hepsiantep\Ai\Install;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class DatabaseInstaller
{
    public function install(): bool
    {
        $engine = _MYSQL_ENGINE_;
        $metaTable = _DB_PREFIX_ . 'hepsiantep_ai_product_meta';
        $evidenceTable = _DB_PREFIX_ . 'hepsiantep_ai_evidence';

        $metaSql = "CREATE TABLE IF NOT EXISTS `$metaTable` (
            `id_ai_meta` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_shop` INT UNSIGNED NOT NULL,
            `id_product` INT UNSIGNED NOT NULL,
            `id_product_attribute` INT UNSIGNED NOT NULL DEFAULT 0,
            `category_type` VARCHAR(64) NOT NULL DEFAULT 'general',
            `sale_unit` VARCHAR(32) NOT NULL DEFAULT 'piece',
            `suitability_json` LONGTEXT NULL,
            `derived_specs_json` LONGTEXT NULL,
            `source_updated_at` DATETIME NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_ai_meta`),
            UNIQUE KEY `uniq_shop_product_attribute` (`id_shop`, `id_product`, `id_product_attribute`),
            KEY `idx_product` (`id_product`, `id_product_attribute`),
            KEY `idx_shop` (`id_shop`)
        ) ENGINE=$engine DEFAULT CHARSET=utf8mb4;";

        $evidenceSql = "CREATE TABLE IF NOT EXISTS `$evidenceTable` (
            `id_evidence` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_shop` INT UNSIGNED NOT NULL,
            `id_product` INT UNSIGNED NOT NULL,
            `id_product_attribute` INT UNSIGNED NOT NULL DEFAULT 0,
            `property_key` VARCHAR(191) NOT NULL,
            `evidence_class` VARCHAR(16) NOT NULL DEFAULT 'declared',
            `source_type` VARCHAR(64) NOT NULL,
            `source_id` VARCHAR(128) NULL,
            `source_url` VARCHAR(512) NULL,
            `confidence` DECIMAL(5,4) NOT NULL DEFAULT 1.0000,
            `evidence_date` DATE NULL,
            `is_public` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            `status` VARCHAR(16) NOT NULL DEFAULT 'active',
            `notes` TEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_evidence`),
            KEY `idx_lookup` (`id_shop`, `id_product`, `id_product_attribute`, `property_key`),
            KEY `idx_class_search` (`evidence_class`, `source_type`),
            KEY `idx_public_status` (`is_public`, `status`)
        ) ENGINE=$engine DEFAULT CHARSET=utf8mb4;";

        return \Db::getInstance()->execute($metaSql)
            && \Db::getInstance()->execute($evidenceSql);
    }

    public function uninstall(): bool
    {
        return true;
    }
}
