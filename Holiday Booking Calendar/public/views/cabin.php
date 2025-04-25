<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table_cabins = $wpdb->prefix . 'hbc_cabins';

// Retrieve cabin data
$cabin_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
if ( ! $cabin_id ) {
    echo '<p>No cabin selected. Please choose a cabin first.</p>';
    return;
}
$cabin = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT cab_id, cab_name, cab_data, cab_price, cab_location, cab_image
         FROM {$table_cabins}
         WHERE cab_id = %d",
        $cabin_id
    )
);
if ( ! $cabin ) {
    echo '<p>Cabin not found.</p>';
    return;
}
// Build image URL
$upload_dir = wp_upload_dir();
$img_url = ! empty( $cabin->cab_image )
    ? trailingslashit( $upload_dir['baseurl'] ) . 'cabins/' . $cabin->cab_image
    : '';

$form_page = get_page_by_path( 'form' );
$form_url  = $form_page ? get_permalink( $form_page->ID ) : home_url( '/form/' );
?>

<div id="cabin-page" class="container cabin-container">
  <div class="cabin-main">
    <div class="cabin-media">
      <?php if ( $img_url ) : ?>
        <div class="cabin-image">
          <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $cabin->cab_name ); ?>" />
        </div>
      <?php endif; ?>
      <div class="cabin-info">
        <h2><?php echo esc_html( $cabin->cab_name ); ?></h2>
        <?php if ( ! empty( $cabin->cab_location ) ) : ?>
          <p class="location"><?php echo esc_html( $cabin->cab_location ); ?></p>
        <?php endif; ?>
        <p class="price"><?php echo esc_html( number_format( $cabin->cab_price, 2 ) ); ?>€ / night</p>
        <p class="description"><?php echo esc_html( $cabin->cab_data ); ?></p>
      </div>
    </div>

    <div class="cabin-booking">
      <form id="hbc-date-form" class="booking-form" method="get" action="<?php echo esc_url( $form_url ); ?>">
        <input type="hidden" id="hbc-cabin" name="cabin_id" value="<?php echo esc_attr( $cabin->cab_id ); ?>">
        <input type="hidden" id="hbc-start-date" name="start_date" value="">
        <input type="hidden" id="hbc-end-date"   name="end_date"   value="">

        <div id="hbc-datepicker" class="datepicker-container" data-cabin-id="<?php echo esc_attr( $cabin->cab_id ); ?>"></div>

        <button type="submit" id="hbc-book-btn" class="select-cabin-btn" >Book Now</button>
      </form>
    </div>
  </div>
</div>