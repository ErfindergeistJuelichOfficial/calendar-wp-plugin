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
 * Hilfsfunktion: escaping für Strings
 * https://mojoauth.com/escaping/php-string-escaping-in-php/
 * 
 * @param string $string Der zu escaped String
 * @return string Escaped String
 */
if (!function_exists('egj_escape')) {

  function egj_escape($string) {
    $newString = trim($string);
    $newString = addslashes($newString);
    return $newString;
  }
}
