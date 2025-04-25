<?php
if (!defined('ABSPATH')) {
    exit;
}

class HBC_DB_Upgrade {
    public static function upgrade() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $installed_version = get_option('hbc_db_version', '1.0');
        $new_version = '1.1';

        if (version_compare($installed_version, $new_version, '<')) {
            $table_definitions = HBC_DB::get_tables($wpdb);

            foreach ($table_definitions as $table => $sql) {
                dbDelta(sprintf($sql, $table));
            }

            update_option('hbc_db_version', $new_version);
        }
    }
}

add_action('plugins_loaded', ['HBC_DB_Upgrade', 'upgrade']);