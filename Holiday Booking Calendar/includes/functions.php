<?php
add_action('wp_ajax_hbc_get_item_details', 'hbc_get_item_details');
add_action('wp_ajax_nopriv_hbc_get_item_details', 'hbc_get_item_details');

function hbc_get_item_details() {
    global $wpdb;

    $id = intval($_POST['id']);
    $type = sanitize_text_field($_POST['type']);

    $upload_dir = wp_upload_dir();
    $cabins_base_url = $upload_dir['baseurl'] . '/cabins/';
    $activities_base_url = $upload_dir['baseurl'] . '/activities/';

    if ($type === 'cabin') {
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hbc_cabins WHERE cab_id = %d", $id), ARRAY_A);
        $base_url = $cabins_base_url;
    } elseif ($type === 'activity') {
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hbc_activities WHERE act_id = %d", $id), ARRAY_A);
        $base_url = $activities_base_url;
    } else {
        wp_send_json_error('Invalid type');
    }

    if ($item) {
        $data = array(
            'name'     => $item['cab_name'] ?? $item['act_name'],
            'location' => $item['cab_location'] ?? $item['act_location'],
            'data'     => $item['cab_data'] ?? $item['act_data'],
            'price'    => $item['cab_price'] ?? $item['act_price'],
            'image'    => $base_url . ($item['cab_image'] ?? $item['act_image']),
        );

        wp_send_json_success($data);
    } else {
        wp_send_json_error('Not found');
    }
}