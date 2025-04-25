<?php

if (!defined('ABSPATH')) {
    exit;
}

class HBC_Calendar {
    public static function init() {
        add_action('wp_ajax_hbc_book_date', [__CLASS__, 'handle_booking']);
        add_action('wp_ajax_nopriv_hbc_book_date', [__CLASS__, 'handle_booking']);
        add_action('wp_ajax_hbc_get_bookings', [__CLASS__, 'get_bookings']);
        add_action('wp_ajax_nopriv_hbc_get_bookings', [__CLASS__, 'get_bookings']);
        add_action('wp_ajax_hbc_get_activities', [__CLASS__, 'get_activities']);
        add_action('wp_ajax_nopriv_hbc_get_activities', [__CLASS__, 'get_activities']);
    }

    public static function handle_booking() {
        global $wpdb;

        // 1) Required fields
        $required = ['start_date','end_date','name','email','phone','cabin_id','reservation_price'];
        foreach ($required as $f) {
            if (empty($_POST[$f])) {
                wp_send_json_error(['message'=> ucfirst($f).' is required.']);
            }
        }

        // 2) Sanitize inputs
        $start  = sanitize_text_field($_POST['start_date']);
        $end    = sanitize_text_field($_POST['end_date']);
        $name   = sanitize_text_field($_POST['name']);
        $email  = sanitize_email($_POST['email']);
        $phone  = sanitize_text_field($_POST['phone']);
        $cabin  = intval($_POST['cabin_id']);
        $price  = floatval($_POST['reservation_price']);
        $acts   = isset($_POST['booked_activities'])
                ? json_decode(stripslashes($_POST['booked_activities']), true)
                : [];

        // 3) Validate email & price
        if (!is_email($email)) {
            wp_send_json_error(['message'=>'Invalid email address.']);
        }
        if ($price <= 0) {
            wp_send_json_error(['message'=>'Calculated price invalid.']);
        }

        // 4) Date diff
        $startDT = new DateTime($start);
        $endDT   = new DateTime($end);
        $days    = $startDT->diff($endDT)->days;

        // 5) Prevent double-booking
        $table   = $wpdb->prefix.'hbc_reservations';
        $count   = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE cabin_id=%d
            AND ((start_date BETWEEN %s AND %s)
                OR (end_date BETWEEN %s AND %s)
                OR (%s BETWEEN start_date AND end_date))",
            $cabin, $start, $end, $start, $end, $start
        ));
        if ($count > 0) {
            wp_send_json_error(['message'=>'Cabin already booked for those dates.']);
        }

        // 6) Insert
        $insert = $wpdb->insert($table, [
            'start_date'        => $start,
            'end_date'          => $end,
            'num_days'          => $days,
            'cabin_id'          => $cabin,
            'reservation_name'  => $name,
            'email'             => $email,
            'phone_number'      => $phone,
            'booking_date'      => current_time('mysql'),
            'reservation_price' => $price,
            'booked_activities' => wp_json_encode((array)$acts)
        ], ['%s','%s','%d','%d','%s','%s','%s','%s','%f','%s']);

        if (false === $insert) {
            error_log('HBC insert error: '.$wpdb->last_error);
            wp_send_json_error(['message'=>'Database error. Please try again.']);
        }

        wp_send_json_success(['message'=>'Booking successful!']);
    }

    // Fetch existing bookings to disable unavailable dates
    public static function get_bookings() {
        global $wpdb;
        $table = $wpdb->prefix . 'hbc_reservations';

        // 1) read the cabin_id from the AJAX args
        $cabin_id = isset( $_REQUEST['cabin_id'] ) 
                ? intval( $_REQUEST['cabin_id'] ) 
                : 0;

        // 2) prepare+execute a filtered query
        $sql = $wpdb->prepare(
            "SELECT start_date, end_date 
            FROM $table
            WHERE cabin_id = %d",
            $cabin_id
        );
        $results = $wpdb->get_results( $sql );

        // 3) build the array of start/end pairs
        $bookings = [];
        foreach( $results as $r ) {
            $bookings[] = [
                'start' => $r->start_date,
                'end'   => $r->end_date,
            ];
        }

        // 4) send it back
        wp_send_json_success( $bookings );
    }
    public static function get_activities() {
        global $wpdb;
        $activities = $wpdb->get_results("SELECT act_id, act_name, act_location FROM {$wpdb->prefix}hbc_activities", ARRAY_A);
        wp_send_json_success($activities);
    }
}

// Initialize AJAX handlers
HBC_Calendar::init();