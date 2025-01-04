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

  public static function get_services_filter_with_cache($args = [])
  {
    $cache_key = BOOKING_PREFIX_QUERY . serialize($args);
    $cache_results = get_transient($cache_key);
    if ($cache_results) {
      return $cache_results;
    }

    // Execute the query
    $services = static::get_services_filter($args);

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

    if ((isset($args["offset"], $args["limit"]) && $args["offset"] > 0) && ($args["limit"] > 0)) {
      $args["offset"] = ($args["offset"] - 1) * $args["limit"];
    }

    $args = wp_parse_args($args, $defaults);

    // Prepare query with pagination
    $query = $wpdb->prepare(
      "
    SELECT 
        s.id AS service_id, 
        s.parent_id, 
        s.service_name, 
        s.description, 
        s.price, 
        s.image_url, 
        s.created_at, 
        s.updated_at, 
        p.service_name AS parent_name 
    FROM 
        {$wpdb->prefix}bookings_services s 
    LEFT JOIN 
        {$wpdb->prefix}bookings_services p 
    ON 
        s.parent_id = p.id 
    ORDER BY 
        COALESCE(s.parent_id, s.id), s.parent_id IS NOT NULL, s.service_name ASC
    LIMIT %d OFFSET %d
    ",
      $args['limit'],
      $args['offset']
    );

    // Execute the query
    $result = $wpdb->get_results($query);
    $services = [];
    foreach ($result as $row) {
      if ($row->parent_id > 0) {
        $services[$row->parent_id]['children'][] = $row;
      } else {
        $services[$row->service_id]['parent'] = $row;
      }
    }

    return $services;
  }

  public static function total_services($limit = 0)
  {
    global $wpdb;
    $result = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}bookings_services");
    $total_page = ceil($result / $limit);

    // return total data and total page
    return [
      'total_data' => $result,
      'total_page' => $total_page
    ];
  }

  public static function get_services_filter($args = [])
  {
    global $wpdb;

    // Default arguments
    $defaults = [
      'limit' => 10,
      'offset' => 0,
      'parent_id' => 0
    ];


    if ((isset($args["offset"], $args["limit"]) && $args["offset"] > 0) && ($args["limit"] > 0)) {
      $args["offset"] = ($args["offset"] - 1) * $args["limit"];
    }

    $args = wp_parse_args($args, $defaults);

    $query = "
      SELECT 
        s.id, 
        s.parent_id, 
        s.service_name, 
        s.description, 
        s.price, 
        s.image_url, 
        s.created_at, 
        s.updated_at
      FROM 
        {$wpdb->prefix}bookings_services s 
      WHERE 1=1";

    $query_args = [];
    if (!is_null($args['parent_id'])) {
      $query .= " AND s.parent_id = %d ";
      $query_args['parent_id'] = $args['parent_id'];
    }


    $query .= "ORDER BY s.service_name ASC LIMIT %d OFFSET %d";
    $query_args[] = $args['limit'];
    $query_args[] = $args['offset'];

    // Prepare the query
    $prepared_query = $wpdb->prepare($query, $query_args);

    $result = $wpdb->get_results($prepared_query);
    return $result;
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
