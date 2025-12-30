<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

function erfindergeist_styles()
{
  wp_enqueue_style(
    'bootstrap',
    plugins_url( '/', __FILE__ ) . 'bootstrap.min.css',
    array(),
    "5.3.3"
  );

  wp_enqueue_style(
    'calender-style',
    plugins_url( '/', __FILE__ ) . 'calender.css',
    array('bootstrap'),
    "2.2"
  );
}

add_action('wp_enqueue_scripts', 'erfindergeist_styles');
