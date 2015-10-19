<?php

// Exit if accessed directly

if ( !defined( 'ABSPATH' ) ) exit;

do_action('nnr_stats_before_autoload_v1');

require_once('model/stats.php');
require_once('controllers/stats.php');

do_action('nnr_stats_before_autoload_v1');