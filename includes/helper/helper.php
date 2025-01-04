<?php

function format_price($amount)
{
  $currency = get_option('bk_booking_currency', 'IDR'); // Get the selected currency
  $currencies = require plugin_dir_path(__FILE__) . 'currencies.php'; // Load the currency list

  if (isset($currencies[$currency])) {
    $symbol = $currencies[$currency]['symbol'];
  } else {
    $symbol = ''; // Default if currency not found
  }

  return $symbol . number_format($amount, 0, '.', ','); // Format the price with commas as thousand separator
}
