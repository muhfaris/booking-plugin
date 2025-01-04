<?php

namespace Booking_Plugin;

class Booking_Query
{
  public static function get_services_with_cache($args = [])
  {
    $cache_key = BOOKING_PREFIX_QUERY . serialize($args);
    $cache_results = get_transient($cache_key);
    if ($cache_results) {
      return $cache_results;
    }

    // Execute the query
    $services = static::get_services($args);

    // Cache the results for 1 hour
    set_transient($cache_key, $services, HOUR_IN_SECONDS);
    return $services;
  }

  public static function get_service_with_cache($id)
  {
    $cache_key = BOOKING_PREFIX_QUERY . $id;
    $cache_results = get_transient($cache_key);
    if ($cache_results) {
      return $cache_results;
    }

    // Execute the query
    $service = static::get_service($id);

    // Cache the results for 1 hour
    set_transient($cache_key, $service, HOUR_IN_SECONDS);
    return $service;
  }

  public static function get_services($args = [])
  {
    global $wpdb;

    // Default arguments
    $defaults = [
      'limit' => 10,
      'offset' => 0
    ];

    $args = wp_parse_args($args, $defaults);

    // Prepare the query
    $query = $wpdb->prepare("
      SELECT id, parent_id, service_name, description, price, image_url
      FROM {$wpdb->prefix}bookings_services
      LIMIT %d OFFSET %d
      ", $args['limit'], $args['offset']);

    // Execute the query
    return $wpdb->get_results($query);
  }

  public static function get_service($id)
  {
    global $wpdb;
    return $wpdb->get_row(
      $wpdb->prepare(
        "
        SELECT id, parent_id, service_name, description, price, image_url
        FROM {$wpdb->prefix}bookings_services WHERE id = %d",
        $id
      )
    );
  }

  public static function create_service($data)
  {
    global $wpdb;
    self::delete_transients();
    $table = "{$wpdb->prefix}bookings_services";
    $result = $wpdb->insert(
      $table,
      [
        'parent_id' => $data['parent_id'],
        'service_name' => $data['service_name'],
        'description' => $data['description'],
        'price' => $data['price'],
        'image_url' => $data['image_url']
      ]
    );

    return $result !== false ? $wpdb->insert_id : false;
  }

  public static function update_service($id, $data)
  {
    global $wpdb;
    self::delete_transients();
    $table = "{$wpdb->prefix}bookings_services";
    return $wpdb->update(
      $table,
      [
        'parent_id' => $data['parent_id'],
        'service_name' => $data['service_name'],
        'description' => $data['description'],
        'price' => $data['price'],
        'image_url' => $data['image_url']
      ],
      ['id' => $id]
    );
  }

  public static function delete_service($id)
  {
    global $wpdb;
    self::delete_transients();
    $table = "{$wpdb->prefix}bookings_services";
    return $wpdb->delete($table, ['id' => $id]);
  }

  public static  function delete_transients()
  {
    // Clear all transients related to your plugin
    global $wpdb;

    $prefix = BOOKING_PREFIX_QUERY; // Define your query prefix constant
    $query = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_' . $wpdb->esc_like($prefix) . '%');
    $wpdb->query($query);

    // Also remove expired transients
    $query = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_' . $wpdb->esc_like($prefix) . '%');
    $wpdb->query($query);
  }
}
