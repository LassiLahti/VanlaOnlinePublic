<?php
/**
 * Plugin Name: Holiday Booking Calendar
 * Plugin URI:  https://yourwebsite.com/
 * Description: A booking calendar with activity recommendations.
 * Version: 1.0.0
 * Author: Lassi Lahti
 * Author URI: https://yourwebsite.com/
 * License: GPL v2 or later
 * Text Domain: holiday-booking-calendar
 * DISCLAIMER: As of the current version this is free software to be distributed for copying, modifying and free use, however NO WARRANTY OR GUARANTEE is given
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Load required files
require_once plugin_dir_path(__FILE__) . 'hbc-loader.php';

// Activation and deactivation hooks
function hbc_activate() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-hbc-db.php';
    HBC_DB::install(); // Call the database setup function
}
register_activation_hook(__FILE__, 'hbc_activate');

// Future implementation
/*
function hbc_deactivate() {

}
register_deactivation_hook(__FILE__, 'hbc_deactivate');
*/