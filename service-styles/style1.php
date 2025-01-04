<?php
if ( !defined( 'IN_SYSTEM' ) ) {
    exit();
}
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
  <img src="<?php echo esc_url( $service['image'] ); ?>" alt="<?php echo esc_attr( $service['name'] ); ?>" class="w-full h-40 object-cover" />
  <div class="p-4">
    <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html( $service['name'] ); ?></h3>
    <p class="text-gray-500 text-sm"><?php echo esc_html( $service['description'] ); ?></p>
    <p class="text-lg font-bold text-green-600 mt-2">Rp <?php echo number_format( $service['price'], 0, ',', '.' ); ?></p>
  </div>
</div>
