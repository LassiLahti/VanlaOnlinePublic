<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table_cabins      = $wpdb->prefix . 'hbc_cabins';
$table_activities  = $wpdb->prefix . 'hbc_activities';

// Retrieve and sanitize GET parameters
$cabin_id   = isset( $_GET['cabin_id'] )   ? intval( $_GET['cabin_id'] )   : 0;
$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : '';
$end_date   = isset( $_GET['end_date'] )   ? sanitize_text_field( $_GET['end_date'] )   : '';
if ( ! $cabin_id || ! $start_date || ! $end_date ) {
    echo '<p>Missing booking details. Please return and select a cabin and dates.</p>';
    return;
}

// Fetch cabin info
$cabin = $wpdb->get_row(
  $wpdb->prepare(
    "SELECT cab_id, cab_name, cab_data, cab_price, cab_location, cab_image
     FROM {$table_cabins}
     WHERE cab_id = %d",
    $cabin_id
  )
);
if ( ! $cabin ) {
    echo '<p>Selected cabin not found.</p>';
    return;
}

// Compute number of nights
$dt_start   = new DateTime( $start_date );
$dt_end     = new DateTime( $end_date );
$interval   = $dt_start->diff( $dt_end );
$num_nights = $interval->days;

// Fetch available activities from DB
$activities = $wpdb->get_results(
  "SELECT act_id, act_name, act_price, act_image
   FROM {$table_activities}"
);

$confirm_url = home_url( '/confirmation/' );


?>

<div id="hbc-booking-container" class="form-page container">
  <script type="text/javascript">
    // Expose the correct confirmation page URL for the JS to use (Stupid but works)
    window.hbc_confirmation_url = "<?php echo esc_url( $confirm_url ); ?>";
  </script>  

  <!-- Activities Section -->
  <section class="activities-section">
    <h2 class="section-heading">Enhance your experience with activities</h2>
    <div id="hbc-activities-container" class="activities-carousel">
      <?php foreach ( $activities as $act ): ?>
        <div class="hbc-activity activity-card"
             data-activity-id="<?php echo esc_attr( $act->act_id ); ?>"
             data-price="<?php echo esc_attr( number_format( $act->act_price, 2 ) ); ?>">
          <?php if ( ! empty( $act->act_image ) ):
            $upload_dir = wp_upload_dir();
            $act_url = trailingslashit( $upload_dir['baseurl'] )
                     . 'activities/'
                     . $act->act_image;
          ?>
            <img src="<?php echo esc_url( $act_url ); ?>"
                 alt="<?php echo esc_attr( $act->act_name ); ?>" />
          <?php endif; ?>
          <h3><?php echo esc_html( $act->act_name ); ?></h3>
          <p class="activity-price"><?php echo esc_html( number_format( $act->act_price, 2 ) ); ?>€</p>
          <button class="select-activity-btn">Add</button>
          <button class="remove-activity-btn" style="display:none;">Remove</button>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Booking Form & Summary -->
  <div class="booking-grid">
    <form id="hbc-book-form" class="booking-form" method="post">
      <?php wp_nonce_field( 'hbc_book_date', 'hbc_nonce' ); ?>
      <input type="hidden" id="hbc-cabin"             name="cabin_id"            value="<?php echo esc_attr( $cabin_id ); ?>">
      <input type="hidden" id="hbc-start-date"        name="start_date"          value="<?php echo esc_attr( $start_date ); ?>">
      <input type="hidden" id="hbc-end-date"          name="end_date"            value="<?php echo esc_attr( $end_date ); ?>">
      <input type="hidden" id="hbc-booked-activities" name="booked_activities"   value="[]">

      <h2 class="section-heading">Your Details</h2>
      <div class="hbc-input-group">
        <label for="hbc-name">Full name:</label>
        <input type="text" id="hbc-name" name="name" required>
      </div>
      <div class="hbc-input-group">
        <label for="hbc-email">Email:</label>
        <input type="email" id="hbc-email" name="email" required>
      </div>
      <div class="hbc-input-group">
        <label for="hbc-phone">Phone 
          <select>
            <option value="fin">&#127467;&#127470; Fin (+358)</option>
            <option value="swe">&#127480;&#127466; Swe (+46)</option>
            <option value="nor">&#127475;&#127476; Nor (+47)</option>
          </select>
        </label>
        <input type="tel" id="hbc-phone" name="phone" required>
      </div>
      <button type="button" id="hbc-book-btn" class="confirm-book-btn">Confirm Booking</button>
      <div id="hbc-message" class="booking-message"></div>
    </form>

    <div id="hbc-pricing-container"
      class="price-summary"
      data-cabin-name="<?php echo esc_attr( $cabin->cab_name ); ?>"
      data-cabin-price="<?php echo esc_attr( $cabin->cab_price ); ?>"
      data-num-nights="<?php echo esc_attr( $num_nights ); ?>">
      <h2 class="section-heading">Booking Summary</h2>
      <div class="hbc-pricing-group">
        <h4>Cabin:</h4>
        <div id="hbc-reservation-details">
          <p><?php echo esc_html( $cabin->cab_name ); ?></p>
        </div>
      </div>
      <div class="hbc-pricing-group">
        <h4>Dates:</h4>
        <p><?php echo esc_html( $start_date ); ?> &rarr; <?php echo esc_html( $end_date ); ?></p>
      </div>
      <div class="hbc-pricing-group">
        <h4>Activities:</h4>
        <div id="hbc-selected-activities">No activities selected.</div>
      </div>
      <div class="hbc-pricing-group">
        <h4>Total:</h4>
        <p id="hbc-reservation-price"><?php echo esc_html( number_format( $cabin->cab_price * $num_nights, 2 ) ); ?>€</p>
      </div>
      </div>
  </div>
</div>