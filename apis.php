<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}



function gcalendar($request)
{

  $apikey_opt_name = 'g_Calendar_apikey';
  $google_calendar_id_opt_name = 'g_Calendar_id';

  if(!get_option( $apikey_opt_name ) && !get_option(  $google_calendar_id_opt_name )) {
    return new WP_Error('rest_custom_error', 'Apikey is not set', array('status' => 400));
  }

  $dateTime = new DateTime();
   
  $currentDate = $dateTime->format(DateTimeInterface::RFC3339);
   
  // google api dislike +00:00. replace with Z
  $currentDate = str_replace("+00:00", "Z", $currentDate);
  
  $gCalendarApiKey = get_option( $apikey_opt_name );
  $gCalendarId = get_option( $google_calendar_id_opt_name );
  $url = 'https://www.googleapis.com/calendar/v3/calendars/'.$gCalendarId.'/events?maxResults=20&orderBy=startTime&singleEvents=true&timeMin=' . $currentDate . '&key='.$gCalendarApiKey;
   
  $content = file_get_contents($url);
 
  return $content;
  // $response = new WP_REST_Response(json_decode($content, true));
  // $response->set_status(200);

  // return $response;
}

function getCalendar($request)
{
  $content = gcalendar($request);
  $response = new WP_REST_Response(json_decode($content, true));
  $response->set_status(200);

  return $response;
}

function getNextEvent($request)
{
  $content = gcalendar($request);
  $obj = json_decode($content, true);

  if(is_array($obj["items"]) && $obj["items"][0]["start"]["dateTime"])
  {

    // 2025-10-22T18:00:00+02:00
    $date_time_pieces = explode("T", $obj["items"][0]["start"]["dateTime"]);

    // 2025-10-22
    $date = $date_time_pieces[0];

    // 2001-03-10
    $tomorrow = new DateTime();
    $tomorrow->modify("+1 day");
    $tomorrow_date = $tomorrow->format("Y-m-d");

    if($date === $tomorrow_date)
    {

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
// https://egj.vreezy.de/wp-json/erfindergeist/v1/gcalendar
add_action('rest_api_init', function () {
  register_rest_route('erfindergeist/v1', '/gcalendar', array(
    'methods'  => 'GET',
    'callback' => 'getCalendar'
  ));
});

add_action('rest_api_init', function () {
  register_rest_route('erfindergeist/v1', '/nextevent', array(
    'methods'  => 'GET',
    'callback' => 'getNextEvent'
  ));
});
