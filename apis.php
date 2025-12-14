<?php

if (!defined('ABSPATH')) {
  exit;
}

require_once 'Event.php';
require_once 'NextEvent.php';
require_once 'ICal.php';
require_once 'vars.php';
use ICal\ICal;

function getIcsFromUrl($ics_url): string|null
{
  try {
    
    $ics = file_get_contents($ics_url);

    return $ics;
  } catch (\Exception $e) {
    egj_send_notification_to_admins("Error getting ICS from URL");
    return "";
  }
}

function getIcsInternal()
{
  $cache_lifetime = 3600; // 1 Stunde
  $ics_url = get_option($_SESSION['ics_url_option_name']);
  $ics_cache = get_option( $_SESSION['ics_cache_option_name'] );
  $ics_cache_timestamp = get_option($_SESSION['ics_cache_timestamp_option_name']);
  $current_time = time();

  $is_cache_valid = $ics_cache && $ics_cache != "";
  
  $is_cache_timeout = $ics_cache_timestamp && ($current_time - $ics_cache_timestamp) < $cache_lifetime;

  $ics = null;
  if (!$ics_cache_timestamp || $is_cache_timeout) 
  {
    try {
      $ics =  getIcsFromUrl($ics_url);
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
    // save new cache
    update_option( $_SESSION['ics_cache_option_name'], $ics );
    update_option( $_SESSION['ics_cache_timestamp_option_name'], $current_time );
  }

  return $ics;


}
function getIcs()
{
  $ics = getIcsInternal();
  
  // Gib ICS-Daten 1:1 zurück wie sie von der URL kommen
  header('Content-Type: text/calendar; charset=utf-8');
  header('Content-Disposition: inline; filename="erfindergeist.ics"');
  echo $ics;
  exit;
}

/**
 * Vereinheitlichter Events-Endpunkt
 */
function getEvents()
{
  try {
    $ics = getIcsInternal();
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

function getNextEvent($request)
{

    $ics = getIcsInternal();
    $iCal = new ICal();
    $iCal->initString($ics);
    $arrayOfEvents = $iCal->eventsFromRange(
      date('Y-m-d H:i:s', strtotime('+1 days')),
      date('Y-m-d H:i:s', strtotime('+2 days'))
    );    

  if (is_array($arrayOfEvents) && count($arrayOfEvents) > 0) {
    $event = $arrayOfEvents[0];

    // Konvertiere Event zu NextEvent
    $nextEvent = new NextEvent();
    $nextEvent->summary = $event->summary ?? '';
    $nextEvent->description = $event->description ?? '';
    $nextEvent->location = $event->location ?? '';
    
    // Formatiere Start- und Endzeit im deutschen Format
    if (isset($event->dtstart_tz)) {
      $startDateTime = new DateTime($event->dtstart_tz);
      $nextEvent->starttime = $startDateTime->format("d.m.Y H:i");
    }
    
    if (isset($event->dtend_tz)) {
      $endDateTime = new DateTime($event->dtend_tz);
      $nextEvent->endtime = $endDateTime->format("d.m.Y H:i");
    }

    $response = new WP_REST_Response($nextEvent);
    $response->set_status(200);
    return $response;
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
  register_rest_route('erfindergeist/v2', '/nextEvent', array(
    'methods' => 'GET',
    'callback' => 'getNextEvent'
  ));
});
