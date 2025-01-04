<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;  // Exit if accessed directly
}
?>
<div class="service-style-2">
  <div class="service-header">
    <h3><?php echo esc_html( $name ); ?></h3>
    <span class="service-price"><?php echo esc_html( $price ); ?></span>
  </div>
  <p><?php echo esc_html( $description ); ?></p>
</div>
