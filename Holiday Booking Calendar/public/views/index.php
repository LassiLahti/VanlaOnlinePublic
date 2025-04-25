<?php
// index.php — Cabin Listings
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$cabins = $wpdb->get_results(
    "SELECT cab_id, cab_name, cab_data, cab_image, cab_price, cab_location FROM {$wpdb->prefix}hbc_cabins",
    ARRAY_A
);
?>

<div id="index-page" class="container">
  <h1>Available Cabins</h1>
  <div class="cabin-grid">
    <?php foreach ( $cabins as $cabin ) : ?>
      <div class="cabin-card">
        <?php if ( ! empty( $cabin['cab_image'] ) ) :
          $upload_dir = wp_upload_dir();
          $img_url = trailingslashit( $upload_dir['baseurl'] ) . 'cabins/' . $cabin['cab_image'];
        ?>
          <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $cabin['cab_name'] ); ?>">
        <?php endif; ?>

        <div class="cabin-card-content">
          <h3><?php echo esc_html( $cabin['cab_name'] ); ?></h3>
          <p><?php echo esc_html( wp_trim_words( $cabin['cab_data'], 20, '...' ) ); ?></p>
          <div class="price">
            <?php echo esc_html( number_format( floatval($cabin['cab_price']), 2 ) ); ?>€ / night
          </div>
          <a href="<?php echo esc_url( add_query_arg( 'id', $cabin['cab_id'], home_url( '/cabin/' ) ) ); ?>" class="select-cabin-btn">
            View Details
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>