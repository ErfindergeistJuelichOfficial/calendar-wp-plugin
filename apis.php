<?php

if (!defined('ABSPATH')) {
  exit;
}

require_once 'Event.php';
require_once 'ICal.php';
require_once 'vars.php';
use ICal\ICal;

function getIcsFromUrl(): string
{
  try {
    $erfindergeist_ics_url = get_option($_SESSION['ics_url_option_name']);
    $ics = file_get_contents($erfindergeist_ics_url);

    // $response = wp_remote_get($erfindergeist_ics_url, array(
    //   'timeout' => 30,
    //   'sslverify' => true
    // ));
    // return wp_remote_retrieve_body($response);
    return $ics;
  } catch (\Exception $e) {
    egj_send_notification_to_admins("Error getting ICS from URL");
    return "";
  }

}

function getIcs(): void
{
  $ics = getIcsFromUrl();
  $response = new WP_REST_Response($ics);
  $response->set_status(200);
}

/**
 * Vereinheitlichter Events-Endpunkt
 */
function getEvents() // $request
{
  try {
    $current_time = time();
    

    if (!get_option($_SESSION['ics_url_option_name'])) {
      $message = "Erfindergeist Calendar: Error missing ICS URL " . date('d.m.Y H:i:s', $current_time) . " for site " . get_site_url() . ".";
      egj_send_notification_to_admins($message);
      return new WP_Error('rest_custom_error', 'Erfindergeist ICS Url is not set', array('status' => 400));
    }

    // Hole gecachte ICS-Daten
    // $cached_ics_data = egj_get_cached_ics_data();

    // if (is_wp_error($cached_ics_data)) {
    //   return $cached_ics_data;
    // }



    // $response = new WP_REST_Response($ics_data );
    // $response->set_status(200);
    // return $response;

    $ics = getIcsFromUrl();
    $iCal = new ICal();
    $iCal->initString($ics);
    $arrayOfEvents = $iCal->eventsFromRange(null, null);

    // Transformiere iCal-Daten in einheitliches Format
    // $transformed_data = egj_transform_ical_data($iCal->cal);

    $response = new WP_REST_Response($arrayOfEvents);
    $response->set_status(200);

    return $response;
  } catch (\Exception $e) {
    egj_send_notification_to_admins("Error parsing ICS data");
    return new WP_Error('rest_custom_error', 'Error parsing ICS data: ' . $e->getMessage(), array('status' => 500));
  }
}

/**
 * Lädt ICS-Daten mit Caching (stündliche Aktualisierung)
 */
function egj_get_cached_ics_data()
{
  $cache_option_name = 'erfindergeist_ics_cache';
  $cache_timestamp_option_name = 'erfindergeist_ics_cache_timestamp';
  $current_time = time();

  $erfindergeist_ics_url = get_option($_SESSION['ics_url_option_name']);

  if (empty($erfindergeist_ics_url)) {
    egj_send_notification_to_admins("Error missing ICS URL");
    return new WP_Error('rest_custom_error', 'Erfindergeist ICS Url is not set', array('status' => 400));
  }

  // Prüfe ob Cache existiert und noch gültig ist (max 1 Stunde alt)
  $cached_data = get_option($cache_option_name);
  $cache_timestamp = get_option($cache_timestamp_option_name);
  $cache_lifetime = 3600; // 1 Stunde in Sekunden

  // Wenn Cache vorhanden und noch gültig, verwende gecachte Daten
  if ($cached_data && $cache_timestamp && ($current_time - $cache_timestamp) < $cache_lifetime) {
    return $cached_data;
  }

  // Cache ist abgelaufen oder existiert nicht, lade neue Daten
  $response = wp_remote_get($erfindergeist_ics_url, array(
    'timeout' => 30,
    'sslverify' => true
  ));

  if (is_wp_error($response)) {
    // Bei Fehler: Wenn alte gecachte Daten vorhanden sind, verwende diese
    if ($cached_data) {
      return $cached_data;
    }

    egj_send_notification_to_admins("Could not reach nextCloud and no cached data available");
    return new WP_Error('rest_custom_error', 'Could not fetch ICS data: ' . $response->get_error_message(), array('status' => 500));
  }

  $ics_data = wp_remote_retrieve_body($response);

  if (empty($ics_data)) {
    // Bei leerem Response: Wenn alte gecachte Daten vorhanden sind, verwende diese
    if ($cached_data) {
      return $cached_data;
    }

    egj_send_notification_to_admins("Could not fetch ICS data and no cached data available");
    return new WP_Error('rest_custom_error', 'ICS data is empty', array('status' => 500));
  }

  // Speichere neue Daten im Cache
  update_option($cache_option_name, $ics_data);
  update_option($cache_timestamp_option_name, $current_time);

  return $ics_data;
}

function getNextEvent($request)
{
  $content = egj_get_google_calendar($request);
  $obj = json_decode($content, true);

  // $iCal->eventsFromRange(
  //   date('Y-m-d H:i:s', strtotime('+1 days')),
  //   date('Y-m-d H:i:s', strtotime('+2 days'))
  // );

  if (is_array($obj["items"]) && $obj["items"][0]["start"]["dateTime"]) {

    // 2025-10-22T18:00:00+02:00
    $date_time_pieces = explode("T", $obj["items"][0]["start"]["dateTime"]);

    // 2025-10-22
    $date = $date_time_pieces[0];

    // 2001-03-10
    $tomorrow = new DateTime();
    $tomorrow->modify("+1 day");
    $tomorrow_date = $tomorrow->format("Y-m-d");

    if ($date === $tomorrow_date) {

      $newObj = $obj["items"][0];
      $newObj["starttime"] = (new DateTime($newObj["start"]["dateTime"]))->format("d.m.Y H:i");
      $response = new WP_REST_Response($newObj);
      $response->set_status(200);
      return $response;
    }
  }

  $response = new WP_REST_Response();
  $response->set_status(304);
  return $response;

}

// CUSTOM APIS
// https://<DOMAIN>/wp-json/erfindergeist/v1/events
add_action('rest_api_init', function () {
  register_rest_route('erfindergeist/v1', '/ics', array(
    'methods' => 'GET',
    'callback' => 'getIcs'
  ));
});

add_action('rest_api_init', function () {
  register_rest_route('erfindergeist/v1', '/events', array(
    'methods' => 'GET',
    'callback' => 'getEvents'
  ));
});

add_action('rest_api_init', function () {
  register_rest_route('erfindergeist/v1', '/nextEvent', array(
    'methods' => 'GET',
    'callback' => 'getNextEvent'
  ));
});
