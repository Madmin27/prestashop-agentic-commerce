<?php

namespace PrestaShopAgenticCommerce\Install;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class DatabaseInstaller
{
    public function install(): bool
    {
        $db = \Db::getInstance();
        if (!$this->migrateLegacyTableNames($db)) {
            return false;
        }

        $engine = _MYSQL_ENGINE_;
        $meta = _DB_PREFIX_ . 'agenticcommerce_product_meta';
        $evidence = _DB_PREFIX_ . 'agenticcommerce_evidence';

        $metaSql = "CREATE TABLE IF NOT EXISTS `$meta` (
            `id_ai_meta` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_shop` INT UNSIGNED NOT NULL,
            `id_product` INT UNSIGNED NOT NULL,
            `id_product_attribute` INT UNSIGNED NOT NULL DEFAULT 0,
            `category_type` VARCHAR(64) NULL,
            `sale_unit` VARCHAR(32) NULL,
            `verified_specs_json` LONGTEXT NULL,
            `declared_specs_json` LONGTEXT NULL,
            `suitability_json` LONGTEXT NULL,
            `derived_specs_json` LONGTEXT NULL,
            `source_updated_at` DATETIME NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_ai_meta`),
            UNIQUE KEY `uniq_shop_product_attribute` (`id_shop`,`id_product`,`id_product_attribute`),
            KEY `idx_product` (`id_product`,`id_product_attribute`),
            KEY `idx_shop` (`id_shop`)
        ) ENGINE=$engine DEFAULT CHARSET=utf8mb4";

        $evidenceSql = "CREATE TABLE IF NOT EXISTS `$evidence` (
            `id_evidence` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_shop` INT UNSIGNED NOT NULL,
            `id_product` INT UNSIGNED NOT NULL,
            `id_product_attribute` INT UNSIGNED NOT NULL DEFAULT 0,
            `property_key` VARCHAR(191) NOT NULL,
            `value_hash` CHAR(64) NULL,
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
            KEY `idx_lookup` (`id_shop`,`id_product`,`id_product_attribute`,`property_key`),
            KEY `idx_value` (`property_key`,`value_hash`),
            KEY `idx_class_search` (`evidence_class`,`source_type`),
            KEY `idx_public_status` (`is_public`,`status`)
        ) ENGINE=$engine DEFAULT CHARSET=utf8mb4";

        if (!$db->execute($metaSql) || !$db->execute($evidenceSql)) {
            return false;
        }

        return $this->ensureCurrentColumns($db);
    }

    public function upgrade(): bool
    {
        $db = \Db::getInstance();
        return $this->migrateLegacyTableNames($db) && $this->ensureCurrentColumns($db);
    }

    /**
     * Merchant metadata is deliberately retained on uninstall. A destructive
     * purge must be an explicit administrative operation.
     */
    public function uninstall(): bool
    {
        return true;
    }

    private function migrateLegacyTableNames(\Db $db): bool
    {
        $renames = [
            _DB_PREFIX_ . 'agentic_product_meta' => _DB_PREFIX_ . 'agenticcommerce_product_meta',
            _DB_PREFIX_ . 'agentic_evidence' => _DB_PREFIX_ . 'agenticcommerce_evidence',
        ];

        foreach ($renames as $legacy => $current) {
            if (!$this->tableExists($db, $legacy) || $this->tableExists($db, $current)) {
                continue;
            }
            if (!$db->execute("RENAME TABLE `$legacy` TO `$current`")) {
                return false;
            }
        }

        return true;
    }

    private function ensureCurrentColumns(\Db $db): bool
    {
        $meta = _DB_PREFIX_ . 'agenticcommerce_product_meta';
        $evidence = _DB_PREFIX_ . 'agenticcommerce_evidence';

        if ($this->tableExists($db, $meta)) {
            if (!$db->execute("ALTER TABLE `$meta`
                MODIFY `category_type` VARCHAR(64) NULL,
                MODIFY `sale_unit` VARCHAR(32) NULL")) {
                return false;
            }
        }

        if ($this->tableExists($db, $evidence) && !$this->columnExists($db, $evidence, 'value_hash')) {
            if (!$db->execute("ALTER TABLE `$evidence` ADD `value_hash` CHAR(64) NULL AFTER `property_key`")) {
                return false;
            }
        }

        if ($this->tableExists($db, $evidence) && !$this->indexExists($db, $evidence, 'idx_value')) {
            if (!$db->execute("ALTER TABLE `$evidence` ADD KEY `idx_value` (`property_key`,`value_hash`)")) {
                return false;
            }
        }

        return true;
    }

    private function tableExists(\Db $db, string $table): bool
    {
        $sql = "SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = '" . pSQL($table) . "'";
        return (int) $db->getValue($sql) > 0;
    }

    private function columnExists(\Db $db, string $table, string $column): bool
    {
        $row = $db->getRow("SHOW COLUMNS FROM `$table` LIKE '" . pSQL($column) . "'");
        return is_array($row) && $row !== [];
    }

    private function indexExists(\Db $db, string $table, string $index): bool
    {
        $row = $db->getRow("SHOW INDEX FROM `$table` WHERE `Key_name` = '" . pSQL($index) . "'");
        return is_array($row) && $row !== [];
    }
}
