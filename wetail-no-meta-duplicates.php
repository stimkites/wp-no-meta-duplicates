<?php
/**
 * Plugin Name: WP No Meta Duplicates
 * Description:  Cleans up Wordpress database from any meta duplicates and prevents further occurrences.
 * Author: Stim (Wetail AB)
 * Version: 0.0.3
 * Author URI: http://wetail.io
 */

namespace Wetail\NoDups;

defined('ABSPATH') or die();

define( __NAMESPACE__ . '\LNG', basename( __DIR__ ) ); //language domain

define( __NAMESPACE__ . '\PATH',  dirname( __FILE__ ) );

define( __NAMESPACE__ . '\INDEX', __FILE__ );

define( __NAMESPACE__ . '\NAME',  basename( __DIR__ ) );

define( __NAMESPACE__ . '\URL',   dirname( plugins_url() ) . '/' . basename( dirname( __DIR__ ) ) . '/' . NAME  ); //mu-compatible

const AJAX_H = 'nodups_aj'; //ajax handle

load_plugin_textdomain( LNG, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

//Load the plugin parts - classes
require "include/classes/class-db-controller.php";
require "include/classes/class-control-panel.php";
require "include/classes/class-ajax.php";
require "include/classes/class-log.php";

//Initialize plugin

//0. Logger
global $_log;
$_log = new _Log();

//1. DB controller
global $_db;
$_db = new _db_Controller();

//2. Ajax
_Ajax::init();

//3. Back-end UI
_Admin::init();

