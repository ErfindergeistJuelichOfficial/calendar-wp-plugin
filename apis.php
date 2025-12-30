<?php

if (!defined('ABSPATH')) {
  exit;
}

require_once 'Event.php';
require_once 'NextEvent.php';
require_once 'ICal.php';
require_once 'vars.php';
use ICal\ICal;

/**
 * Fetches ICS calendar data from a given URL
 * 
 * @param string $ics_url The URL to fetch the ICS file from
 * @return string|null The ICS content as string, or null on error
 */
function get_ics_from_url($ics_url): string|null
{
  try {
    $context = stream_context_create(array(
      'http' => array(
        'timeout' => 7
      )
    ));
    
    $ics = file_get_contents($ics_url, false, $context);

    return $ics;
  } catch (\Exception $e) {
    egj_send_notification_to_admins("Error getting ICS from URL");
    return null;
  }
}

/**
 * Internal function to get ICS data with caching
 * Fetches ICS from URL or returns cached version if still valid
 * Cache lifetime is 1 hour (3600 seconds)
 * 
 * @return string|null The ICS content as string, or null on error
 */
function get_ics_internal()
{
  $cache_lifetime = 3600; // 1 hour
  $ics_url = get_option($_SESSION['ics_url_option_name']);
  $ics_cache = get_option( $_SESSION['ics_cache_option_name'] );
  $ics_cache_timestamp = get_option($_SESSION['ics_cache_timestamp_option_name']);
  $current_time = time();

  $is_cache_valid = $ics_cache && $ics_cache != "";
  
  $has_cache_expired = $ics_cache_timestamp && ($current_time - $ics_cache_timestamp) > $cache_lifetime;

  $ics = null;
  if (!$ics_cache_timestamp || $has_cache_expired) 
  {
    try {
      $ics =  get_ics_from_url($ics_url);
    }catch (\Exception $e) {
      egj_send_notification_to_admins("Error getting ICS from URL: $ics_url");
      $ics = null;
    }
  }

  if (!$ics || $ics == "") 
  {
    if ($is_cache_valid) 
    {
      $ics = $ics_cache;
    } 
    else 
    {
      egj_send_notification_to_admins("Error getting ICS from URL and no cached data available, ICS_URL: $ics_url");
    }
  } 
  else 
  {
    // Save new cache
    update_option( $_SESSION['ics_cache_option_name'], $ics );
    update_option( $_SESSION['ics_cache_timestamp_option_name'], $current_time );
  }

  return $ics;
}

/**
 * REST API endpoint handler that returns raw ICS calendar data
 * Sets appropriate headers and outputs ICS file directly
 * 
 * @return void Outputs ICS data and exits
 */
function get_ics()
{
  $ics = get_ics_internal();
  
  // Return ICS data as-is from the URL
  header('Content-Type: text/calendar; charset=utf-8');
  header('Content-Disposition: inline; filename="erfindergeist.ics"');
  echo $ics;
  exit;
}

/**
 * Unified events endpoint
 * Returns all calendar events as JSON via WordPress REST API
 * 
 * @return WP_REST_Response|WP_Error Response with array of events or error object
 */
function get_events()
{
  try {
    $ics = get_ics_internal();
    $iCal = new ICal();
    $iCal->initString($ics);
    $arrayOfEvents = $iCal->eventsFromRange(null, null);

    $response = new WP_REST_Response($arrayOfEvents);
    $response->set_status(200);

    return $response;
  } catch (\Exception $e) {
    egj_send_notification_to_admins("Error parsing ICS data");
    return new WP_Error('rest_custom_error', 'Error parsing ICS data: ' . $e->getMessage(), array('status' => 500));
  }
}

/**
 * REST API endpoint that returns the first event occurring tomorrow
 * Returns a NextEvent object with formatted date/time information
 * 
 * @return WP_REST_Response Response with NextEvent object (200) or empty response (404)
 */
function get_tomorrow_event()
{
    $ics = get_ics_internal();
    $iCal = new ICal();
    $iCal->initString($ics);
    $arrayOfEvents = $iCal->eventsFromRange(
      date('Y-m-d 00:00:00', strtotime('tomorrow')),
      date('Y-m-d 23:59:59', strtotime('tomorrow'))
    );    

  if (is_array($arrayOfEvents) && count($arrayOfEvents) > 0) {
    $event = $arrayOfEvents[0];

    // Convert Event to NextEvent
    $nextEvent = new NextEvent();
    $nextEvent->summary = $event->summary ?? '';
    $nextEvent->description = $event->description ?? '';
    $nextEvent->location = $event->location ?? '';
    
    // Format start and end time in German format
    if (isset($event->dtstart)) {
      $startDateTime = new DateTime($event->dtstart);
      $nextEvent->starttime = $startDateTime->format("d.m.Y H:i");
    }
    
    if (isset($event->dtend)) {
      $endDateTime = new DateTime($event->dtend);
      $nextEvent->endtime = $endDateTime->format("d.m.Y H:i");
    }

    $response = new WP_REST_Response($nextEvent);
    $response->set_status(200);
    return $response;
  }

  $response = new WP_REST_Response();
  $response->set_status(404);
  return $response;
}

// CUSTOM APIS
// https://<DOMAIN>/wp-json/erfindergeist/v2/
add_action('rest_api_init', function () {
  // Register ICS endpoint
  register_rest_route('erfindergeist/v2', '/ics', array(
    'methods' => 'GET',
    'callback' => 'get_ics',
    'permission_callback' => '__return_true'
  ));

  // Register events endpoint
  register_rest_route('erfindergeist/v2', '/events', array(
    'methods' => 'GET',
    'callback' => 'get_events',
    'permission_callback' => '__return_true'
  ));

  // Register next event endpoint
  register_rest_route('erfindergeist/v2', '/tomorrow', array(
    'methods' => 'GET',
    'callback' => 'get_tomorrow_event',
    'permission_callback' => '__return_true'
  ));
});
