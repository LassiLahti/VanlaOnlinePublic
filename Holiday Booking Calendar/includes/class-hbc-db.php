<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class HBC_DB {
    private static function get_tables($wpdb) {
        $charset_collate = $wpdb->get_charset_collate();
        $tables = [
            "{$wpdb->prefix}hbc_reservations" => "
                reservation_number BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                reservation_name VARCHAR(255) NOT NULL,
                reservation_price INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone_number VARCHAR(20) NOT NULL,
                booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                num_days INT NOT NULL,
                cabin_id INT NOT NULL,
                location VARCHAR(255) NOT NULL,
                payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
                booked_activities LONGTEXT NULL,
                PRIMARY KEY (reservation_number)
            ",
            "{$wpdb->prefix}hbc_activities" => "
                act_id BIGINT NOT NULL AUTO_INCREMENT,
                act_name VARCHAR(255) NOT NULL,
                act_location VARCHAR(255),
                act_image VARCHAR(255),
                act_price INT,
                act_data VARCHAR(255),
                PRIMARY KEY (act_id)
            ",
            "{$wpdb->prefix}hbc_cabins" => "
                cab_id BIGINT NOT NULL AUTO_INCREMENT,
                cab_name VARCHAR(255) NOT NULL,
                cab_location VARCHAR(255),
                cab_image VARCHAR(255),
                cab_data VARCHAR(255),
                cab_price INT,
                PRIMARY KEY (cab_id)
            "
        ];

        return array_map(fn($schema) => "CREATE TABLE %s ($schema) $charset_collate;", $tables);
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach (self::get_tables($wpdb) as $table => $sql) {
            dbDelta(sprintf($sql, $table));

            if ($wpdb->last_error) {
                error_log("Database Error on $table: " . $wpdb->last_error);
            }
        }

        update_option('hbc_db_version', '1.0');
    }
}


register_activation_hook(__FILE__, ['HBC_DB', 'install']);