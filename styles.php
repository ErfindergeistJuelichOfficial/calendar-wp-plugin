<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

// we need this to load the parent styles
// https://developer.wordpress.org/themes/advanced-topics/child-themes/
function erfindergeist_styles()
{
  wp_enqueue_style(
    'loading-shimmer-style',
    plugins_url( '/', __FILE__ ) . 'loading-shimmer.css',
    array(),
    "1.0"
  );

  wp_enqueue_style(
    'bootstrap',
    plugins_url( '/', __FILE__ ) . 'bootstrap.min.css',
    array(),
    "5.3.3"
  );

  wp_enqueue_style(
    'gcalender-style',
    plugins_url( '/', __FILE__ ) . 'gcalender.css',
    array('bootstrap'),
    "1.4"
  );

  wp_enqueue_script(
    'handlebars',
    plugins_url( '/', __FILE__ ) . 'handlebars.js',
    array('jquery'),
    "4.7.8"
  );

  wp_enqueue_script(
    'gcalendar-script',
    plugins_url( '/', __FILE__ ) . 'gcalendar.js',
    array('jquery', 'handlebars'),
    "1.4"
  );
}

add_action('wp_enqueue_scripts', 'erfindergeist_styles');
