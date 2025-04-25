<?php
if (!defined('ABSPATH')) {
    exit;
}

class HBC_Shortcodes {
    public static function init() {
        // Legacy shortcodes
        add_shortcode('hbc_calendar',    [__CLASS__, 'render_calendar']);
        add_shortcode('hbc_activities',  [__CLASS__, 'render_activities']);

        // Booking flow shortcodes
        add_shortcode('hbc_list_cabins',    [__CLASS__, 'render_list_cabins']);
        add_shortcode('hbc_cabin',          [__CLASS__, 'render_cabin']);
        add_shortcode('hbc_form',           [__CLASS__, 'render_form']);
        add_shortcode('hbc_confirmation',   [__CLASS__, 'render_confirmation']);
    }

    public static function render_calendar() {
        ob_start();
        include plugin_dir_path(__FILE__) . '../public/views/calendar.php';
        return ob_get_clean();
    }

    public static function render_activities() {
        ob_start();
        include plugin_dir_path(__FILE__) . '../public/views/activities.php';
        return ob_get_clean();
    }

    public static function render_list_cabins() {
        ob_start();
        include plugin_dir_path(__FILE__) . '../public/views/index.php';
        return ob_get_clean();
    }

    public static function render_cabin($atts) {
        $atts    = shortcode_atts(['id'=>0], $atts, 'hbc_cabin');
        $url_id  = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $cabin_id = $url_id ?: intval($atts['id']);
        if (!$cabin_id) {
            return '<p>No cabin specified.</p>';
        }
        $_GET['id'] = $cabin_id;
        // Lookup form page URL
        $page = get_page_by_path('confirmation'); 
        $form_endpoint = $page ? get_permalink($page) : home_url('/confirmation');
        ob_start();
        include plugin_dir_path(__FILE__) . '../public/views/cabin.php';
        return ob_get_clean();
    }

    public static function render_form() {
        // Validate incoming GET parameters
        $required = ['cabin_id','start_date','end_date'];
        foreach ($required as $r) {
            if (empty($_GET[$r])) {
                return '<p>Booking data missing. Please start <a href="'.esc_url(home_url('/cabin-listings')).'">here</a>.</p>';
            }
        }
        // Determine confirmation page (will process POST)
        $confirm_page = get_page_by_path( 'confirmation' );
        $confirm_url  = $confirm_page ? get_permalink( $confirm_page->ID ) : home_url( '/confirmation/' );
        ob_start();

        include plugin_dir_path(__FILE__) . '../public/views/form.php';
        return ob_get_clean();
    }

    public static function render_confirmation() {
        ob_start();
        include plugin_dir_path(__FILE__) . '../public/views/confirmation.php';
        return ob_get_clean();
    }
}