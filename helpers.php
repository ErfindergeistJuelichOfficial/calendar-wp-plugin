<?php

/**
 * Helper Functions für Erfindergeist Calendar Plugin
 * 
 * @package Erfindergeist-Calendar
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * Sendet Benachrichtigungen an alle Administrator-Benutzer
 * 
 * @param string $message Die zu sendende Nachricht
 * @param string $subject Optional: Betreff der E-Mail (Standard: 'Erfindergeist Calendar Notification')
 * @return bool True wenn mindestens eine Mail gesendet wurde, sonst false
 */
if (!function_exists('egj_send_notification_to_admins')) {
  function egj_send_notification_to_admins($message, $subject = 'Erfindergeist Calendar Notification') {
    $admins = get_users(array('role' => 'administrator'));
    $current_time = time();
    $message .= " \n ------------------ \n ";
    $message .= " Server dateTime: " . date('d.m.Y H:i:s', $current_time) . " \n ";
    $message .= " Site URL: " . get_site_url() . " \n ";
    $sent = false;
    
    foreach ($admins as $admin) {
      $result = wp_mail(
        $admin->user_email,
        $subject,
        $message
      );
      
      if ($result) {
        $sent = true;
      }
    }
    
    return $sent;
  }
}

/**
 * Transformiert Google Calendar Daten in ein einheitliches Format
 * 
 * @param array $data Google Calendar API Response
 * @return array Normalisierte Event-Daten
 */
if (!function_exists('egj_transform_google_calendar_data')) {
  function egj_transform_google_calendar_data($data) {
    if (!isset($data['items']) || !is_array($data['items'])) {
      return array('items' => array());
    }

    $transformed_items = array();

    foreach ($data['items'] as $item) {
      // Extrahiere Tags aus Beschreibung
      $description = isset($item['description']) ? $item['description'] : '';
      preg_match_all('/#[a-zA-Z0-9äüö]+/', $description, $matches);
      $tags = isset($matches[0]) ? $matches[0] : array();
      
      // Entferne Tags aus Beschreibung
      $filtered_description = $description;
      foreach ($tags as $tag) {
        $filtered_description = str_replace($tag, '', $filtered_description);
      }
      $filtered_description = trim($filtered_description);

      // Entferne # von Tags
      $clean_tags = array_map(function($tag) {
        return ltrim($tag, '#');
      }, $tags);

      // Verarbeite Datum/Zeit
      $start_datetime = isset($item['start']['dateTime']) ? $item['start']['dateTime'] : '';
      $end_datetime = isset($item['end']['dateTime']) ? $item['end']['dateTime'] : '';

      $start_date = '';
      $end_date = '';
      $start_date_day = '';
      $end_date_day = '';
      $start_time = '';
      $end_time = '';
      $weekday_short = '';
      $same_day = false;

      if ($start_datetime) {
        $start_dt = new DateTime($start_datetime);
        $start_date = $start_dt->format('d.m.Y');
        $start_date_day = $start_dt->format('d');
        $start_time = $start_dt->format('H:i');
        $weekday_short = egj_get_german_weekday_short($start_dt);
      }

      if ($end_datetime) {
        $end_dt = new DateTime($end_datetime);
        $end_date = $end_dt->format('d.m.Y');
        $end_date_day = $end_dt->format('d');
        $end_time = $end_dt->format('H:i');
      }

      $same_day = ($start_date === $end_date);

      $transformed_items[] = array(
        'summary' => isset($item['summary']) ? $item['summary'] : '',
        'description' => $filtered_description,
        'location' => isset($item['location']) ? $item['location'] : '',
        'startDate' => $start_date,
        'startDateDay' => $start_date_day,
        'endDate' => $end_date,
        'endDateDay' => $end_date_day,
        'startTime' => $start_time,
        'endTime' => $end_time,
        'weekDayShort' => $weekday_short,
        'sameDay' => $same_day,
        'tags' => $clean_tags
      );
    }

    return array('items' => $transformed_items);
  }
}

/**
 * Transformiert iCal/Nextcloud Daten in ein einheitliches Format
 * 
 * @param array $data iCal Parser Response
 * @return array Normalisierte Event-Daten
 */
if (!function_exists('egj_transform_ical_data')) {
  function egj_transform_ical_data($data) {
    if (!isset($data['VEVENT']) || !is_array($data['VEVENT'])) {
      return array('items' => array());
    }

    $transformed_items = array();

    foreach ($data['VEVENT'] as $item) {
      // Extrahiere Tags aus Beschreibung
      $description = isset($item['DESCRIPTION']) ? $item['DESCRIPTION'] : '';
      preg_match_all('/#[a-zA-Z0-9äüö]+/', $description, $matches);
      $tags = isset($matches[0]) ? $matches[0] : array();
      
      // Entferne Tags aus Beschreibung
      $filtered_description = $description;
      foreach ($tags as $tag) {
        $filtered_description = str_replace($tag, '', $filtered_description);
      }
      $filtered_description = trim($filtered_description);

      // Entferne # von Tags
      $clean_tags = array_map(function($tag) {
        return ltrim($tag, '#');
      }, $tags);

      // Verarbeite Datum/Zeit
      $start_date_day = '';
      $end_date_day = '';
      $start_time = '';
      $end_time = '';
      $weekday_short = '';
      $start_date = '';
      $end_date = '';
      $same_day = false;

      if (isset($item['DTSTART_array']) && isset($item['DTSTART_array'][2])) {
        $start_dt = new DateTime($item['DTSTART_array'][2]);
        $start_date = $start_dt->format('d.m.Y');
        $start_date_day = $start_dt->format('d');
        $start_time = $start_dt->format('H:i');
        $weekday_short = egj_get_german_weekday_short($start_dt);
      }

      if (isset($item['DTEND_array']) && isset($item['DTEND_array'][2])) {
        $end_dt = new DateTime($item['DTEND_array'][2]);
        $end_date = $end_dt->format('d.m.Y');
        $end_date_day = $end_dt->format('d');
        $end_time = $end_dt->format('H:i');
      }

      $same_day = ($start_date === $end_date);

      $transformed_items[] = array(
        'summary' => isset($item['SUMMARY']) ? $item['SUMMARY'] : '',
        'description' => $filtered_description,
        'location' => isset($item['LOCATION']) ? $item['LOCATION'] : '',
        'startDate' => $start_date,
        'startDateDay' => $start_date_day,
        'endDate' => $end_date,
        'endDateDay' => $end_date_day,
        'startTime' => $start_time,
        'endTime' => $end_time,
        'weekDayShort' => $weekday_short,
        'sameDay' => $same_day,
        'tags' => $clean_tags
      );
    }

    return array('items' => $transformed_items);
  }
}

/**
 * Hilfsfunktion: Gibt deutschen Wochentag in Kurzform zurück
 * 
 * @param DateTime $date Das Datum
 * @return string Kurzform des Wochentags (Mo, Di, Mi, etc.)
 */
if (!function_exists('egj_get_german_weekday_short')) {
  function egj_get_german_weekday_short($date) {
    $weekdays = array('So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa');
    return $weekdays[(int)$date->format('w')];
  }
}
