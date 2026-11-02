<?php

if ( ! defined( 'ABSPATH' ) ) exit; 

// 1. Include all tab files from the subfolder
include_once plugin_dir_path(__FILE__) . 'auth/ahbn-frontend-auth.php';
include_once plugin_dir_path(__FILE__) . 'rooms/ahbn-frontend-rooms.php';
include_once plugin_dir_path(__FILE__) . 'login/ahbn-frontend-login.php';
include_once plugin_dir_path(__FILE__) . 'registration/ahbn-frontend-register.php';
include_once plugin_dir_path(__FILE__) . 'search/ahbn-frontend-search-form.php';
include_once plugin_dir_path(__FILE__) . 'account/ahbn-frontned-account.php';
include_once plugin_dir_path(__FILE__) . 'single/ahbn-frontned-room-single.php';
include_once plugin_dir_path(__FILE__) . 'thanks/ahbn-frontned-thanks.php';