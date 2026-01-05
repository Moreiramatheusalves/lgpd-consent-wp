<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_DB
{
    public static function table_name(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'brlgpd_consent_log';
    }

    public static function maybe_create_tables(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        global $wpdb;

        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            created_at DATETIME NOT NULL,
            consent_hash CHAR(64) NOT NULL,
            policy_version VARCHAR(32) NOT NULL DEFAULT '',
            choices LONGTEXT NOT NULL,
            PRIMARY KEY  (id),
            KEY consent_hash (consent_hash),
            KEY created_at (created_at)
        ) {$charset};";

        dbDelta($sql);
    }

    public static function drop_tables(): void
    {
        global $wpdb;
        $table = self::table_name();
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
}
