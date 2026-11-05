<?php
/**
 * Room Management Module Loader.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$ahbn_rooms_dir = plugin_dir_path( __FILE__ ) . 'rooms/';

require_once $ahbn_rooms_dir . 'room-delete.php';
require_once $ahbn_rooms_dir . 'room-list.php';
require_once $ahbn_rooms_dir . 'room-add-edit.php';
require_once $ahbn_rooms_dir . 'room-view.php';
require_once $ahbn_rooms_dir . 'room-extras.php';
require_once $ahbn_rooms_dir . 'room-tabs.php';