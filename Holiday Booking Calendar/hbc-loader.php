<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include core classes
require_once plugin_dir_path(__FILE__) . 'includes/class-hbc-calendar.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hbc-db.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hbc-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'admin/views/settings-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';


// Enqueue scripts and styles
function hbc_enqueue_assets() {
    wp_enqueue_style('hbc-styles', plugin_dir_url(__FILE__) . 'assets/css/style.css');

    wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', [], null, true);
    wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
}
add_action('wp_enqueue_scripts', 'hbc_enqueue_assets');

function hbc_enqueue_form_scripts() {
  if ( is_page('form') ) {
    wp_enqueue_script('hbc-form-js', plugins_url('js/form.js', __FILE__), ['jquery','flatpickr'], '1.0', true);
  }
}
add_action('wp_enqueue_scripts', 'hbc_enqueue_form_scripts');

function enqueue_hbc_booking_scripts() {
    // Enqueue script
    wp_enqueue_script('hbc-main-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', ['jquery'], null, true);
    // Localize script to pass AJAX URL
    wp_localize_script('hbc-main-script', 'hbc_ajax', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_hbc_booking_scripts');

// Add Admin Menu
function hbc_add_admin_menu() {
    add_menu_page(
        'HBC Reservations',      // Page title
        'HBC Reservations',      // Menu title
        'manage_options',        // Capability
        'hbc_reservations',      // Menu slug (used to identify the page)
        'hbc_admin_dash_page',   // Callback function to display the page content
        'dashicons-calendar',    // Icon (optional, use a WordPress dashicon)
        6                        // Position of the menu item
    );
}
add_action('admin_menu', 'hbc_add_admin_menu');

// Enqueue Scripts and Styles
function hbc_admin_assets($hook) { 
    // Make sure we're on the correct admin page
    if ($hook !== 'toplevel_page_hbc_reservations') {
        return;
    }

    // Enqueue admin styles and scripts
    wp_enqueue_style('hbc-admin-styles', plugin_dir_url(__FILE__) . 'admin/assets/css/admin.css');
    wp_enqueue_script('hbc-admin-scripts', plugin_dir_url(__FILE__) . 'admin/assets/js/admin.js', array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'hbc_admin_assets');


// Initialize shortcodes
HBC_Shortcodes::init();

// Initialize calendar
HBC_Calendar::init();