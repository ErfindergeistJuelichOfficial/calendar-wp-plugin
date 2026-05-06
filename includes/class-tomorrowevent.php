<?php
/**
 * Datenmodell für das morgige Event.
 *
 * @package Erfindergeist-Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Datenmodell für das erste Event von morgen (REST-API-Antwort).
 */
class TomorrowEvent {

	/** @var string */
	public string $summary = '';

	/** @var string */
	public string $description = '';

	/** @var string */
	public string $location = '';

	/** @var string */
	public string $starttime = '';

	/** @var string */
	public string $endtime = '';
}
