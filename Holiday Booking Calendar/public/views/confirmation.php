<?php
if ( ! defined('ABSPATH') ) exit;

// Read and sanitize cookies
$cabin_id = isset($_COOKIE['hbc_cabin']) ? intval($_COOKIE['hbc_cabin']) : 0;
$name     = isset($_COOKIE['hbc_name'])  ? sanitize_text_field( urldecode($_COOKIE['hbc_name']) ) : '';

if ( ! $cabin_id || ! $name ) {
  echo '<p>Invalid or expired confirmation link. Please start over.</p>';
  return;
}

// Clear the cookies immediately after reading
setcookie('hbc_cabin', '', time() - 3600, '/');
setcookie('hbc_name',  '', time() - 3600, '/');

global $wpdb;

// 2. Look up the most recent reservation for this user & cabin
$table = $wpdb->prefix . 'hbc_reservations';
$reservation = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$table}
         WHERE reservation_name = %s AND cabin_id = %d
         ORDER BY booking_date DESC
         LIMIT 1",
        $name, $cabin_id
    )
);

if ( ! $reservation ) {
    echo '<p>No reservation found. Please contact support.</p>';
    return;
}

// 3. Decode activities
$activities = json_decode( $reservation->booked_activities, true );

// 4. Calculate total price
// 4a. Cabin cost
$cabin_price  = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT cab_price FROM {$wpdb->prefix}hbc_cabins WHERE cab_id = %d",
        $cabin_id
    )
);
$total_price  = (float) $cabin_price * intval( $reservation->num_days );

// 4b. Activity cost and names
$activity_lines = [];
if ( ! empty( $activities ) ) {
    $placeholders = implode(',', array_fill(0, count($activities), '%d'));
    $sql = $wpdb->prepare(
        "SELECT act_name, act_price FROM {$wpdb->prefix}hbc_activities WHERE act_id IN ({$placeholders})",
        ...$activities
    );
    $acts = $wpdb->get_results( $sql, ARRAY_A );
    foreach ( $acts as $act ) {
        $activity_lines[] = esc_html($act['act_name']) . ' (€' . esc_html($act['act_price']) . ')';
        $total_price    += (float) $act['act_price'];
    }
}

// 5. Display confirmation
?>
<div class="hbc-confirmation">
  <h1>Booking Confirmed!</h1>
  <p>Thank you, <strong><?php echo esc_html($reservation->reservation_name); ?></strong>.</p>

  <h2>Reservation Details</h2>
  <ul>
    <li><strong>Reservation number:</strong> <?php echo esc_html($reservation->reservation_number); ?></li>
    <li><strong>Dates:</strong> <?php echo esc_html($reservation->start_date); ?> &rarr; <?php echo esc_html($reservation->end_date); ?> (<?php echo esc_html($reservation->num_days); ?> night(s))</li>
    <li><strong>Activities:</strong><br>
      <?php
      if ( $activity_lines ) {
          echo implode('<br>', $activity_lines);
      } else {
          echo 'None selected.';
      }
      ?>
    </li>
    <li><strong>Total Charged:</strong> €<?php echo number_format($total_price, 2); ?></li>
  </ul>

  <p><a href="<?php echo esc_url(home_url('/')); ?>">Return to home</a></p>
</div>