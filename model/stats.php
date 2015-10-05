<?php

// Exit if accessed directly

if ( !defined( 'ABSPATH' ) ) exit;

// Check if class already exists

if ( !class_exists("NNR_Stats_Tracker_v1") ):

/* ================================================================================
 *
 * Data Manger is a MVC addon to help you manager custom data within custom tables
 * in WordPress.
 *
 ================================================================================ */

if ( !class_exists('NNR_Stats_Tracker_Base_v1') ) {
	require_once( dirname(dirname(__FILE__)) . '/base.php');
}

/**
 * NNR_Stats_Tracker_v1 class.
 */
class NNR_Stats_Tracker_v1 extends NNR_Stats_Tracker_Base_v1 {

	/**
	 * table_name
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access private
	 */
	private $table_name = '';

	/**
	 * Create a new instance of the Data Manager class and set the table name
	 *
	 * @access public
	 * @param mixed $table_name
	 * @return void
	 */
	function __construct($table_name) {

		do_action('nnr_stats_before_new_model_v1');

		$this->table_name = $table_name;

		do_action('nnr_stats_after_new_model_v1');
	}

	/**
	 * Create the table
	 *
	 * @access public
	 * @param mixed $table_name
	 * @return void
	 */
	function create_table() {

		do_action('nnr_stats_before_create_table_v1');

		global $wpdb;

		$result = $wpdb->query( apply_filters('nnr_stats_create_table_v1', "
			CREATE TABLE IF NOT EXISTS `" . $this->get_table_name() . "` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`data_id` int(11) NOT NULL DEFAULT 0,
				`date` date NOT NULL,
				`impressions` int(8) NOT NULL DEFAULT 0,
				`conversions` int(8) NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
		") );

		do_action('nnr_stats_after_create_table_v1');

		return $result;
	}

	/**
	 * Get the stats for all optin fires in a timeframe or a single firbar in a timeframe
	 *
	 * @access public
	 * @static
	 * @param mixed $start
	 * @param mixed $end
	 * @param mixed $id (default: null)
	 * @return void
	 */
	function get_stats($start = null, $end = null, $id = null, $select = '*') {

		do_action('nnr_stats_before_get_stats_v1');

		$query = null;

		global $wpdb;

		// All Optins, All Time

		if ($start == null && $end == null && $id == null) {
			$query = 'SELECT ' . $select . ' FROM ' . $this->get_table_name();
		}

		// All Optins, After Date

		else if ($start != null && $end == null && $id == null) {
			$query = $wpdb->prepare('SELECT ' . $select . ' FROM ' . $this->get_table_name() . ' WHERE `date` = %s', $start);
		}

		// All Optins, Date Range

		else if ($start != null && $end != null && $id == null) {
			$query = $wpdb->prepare('SELECT ' . $select . ' FROM ' . $this->get_table_name() . ' WHERE `date` >= %s AND `date` <= %s', $start, $end);
		}

		// Single Optin, All Time

		else if ($start == null && $end == null && $id != null) {
			$query = $wpdb->prepare('SELECT ' . $select . ' FROM ' . $this->get_table_name() . ' WHERE `data_id` = %d', $id);
		}

		// Single Optin, Date Range

		else if ($start != null && $end != null && $id != null) {
			$query = $wpdb->prepare('SELECT ' . $select . ' FROM ' . $this->get_table_name() . ' WHERE `date` >= %s AND `date` <= %s AND `data_id` = %d', $start, $end, $id);
		}

		// Single Optin, After Date

		else if ($start != null && $end == null && $id != null) {
			$query = $wpdb->prepare('SELECT ' . $select . ' FROM ' . $this->get_table_name() . ' WHERE `date` >= %s AND `data_id` = %d', $start, $id);
		}

		// No query was created

		if (!isset($query)) {
			return false;
		}

		$result = $wpdb->get_results( apply_filters('nnr_stats_get_stats_v1', $query) , 'ARRAY_A');

		do_action('nnr_stats_after_get_stats_v1');

		return $result;
	}

	/**
	 * Get the stats from a certain data type
	 *
	 * @access public
	 * @param mixed $id
	 * @param mixed $start (default: null)
	 * @param mixed $end (default: null)
	 * @return void
	 */
	function get_stats_from_id( $id, $start = null, $end = null ) {

		do_action('nnr_stats_before_get_stats_from_id_v1');

		$query = null;

		global $wpdb;

		// Single Optin, All Time

		if ($start == null && $end == null) {
			$query = $wpdb->prepare('SELECT * FROM ' . $this->get_table_name() . ' WHERE `data_id` = %d', $id);
		}

		// Single Optin, Date Range

		else if ($start != null && $end != null) {
			$query = $wpdb->prepare('SELECT * FROM ' . $this->get_table_name() . ' WHERE `date` >= %s AND `date` <= %s AND `data_id` = %d', $start, $end, $id);
		}

		// Single Optin, After Date

		else if ($start != null && $end == null) {
			$query = $wpdb->prepare('SELECT * FROM ' . $this->get_table_name() . ' WHERE `date` >= %s AND `data_id` = %d', $start, $id);
		}

		// No query was created

		if (!isset($query)) {
			return false;
		}

		$result = $wpdb->get_results( apply_filters('nnr_stats_get_stats_from_id_v1', $query), 'ARRAY_A');

		do_action('nnr_stats_after_get_stats_from_id_v1');

		return $result;

	}

	/**
	 * Deletes the stats for a data instance
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	function delete_stats( $data_id ) {

		do_action('nnr_stats_after_delete_stats_v1');

		// Return false if no data id given

		if ( !isset($data_id) ) {
			return false;
		}

		global $wpdb;
		$result = $wpdb->query( apply_filters('nnr_stats_delete_stats_v1', $wpdb->prepare('DELETE FROM ' . $this->getTableName() . ' WHERE `data_id` = %d', $data_id) ) );

		do_action('nnr_stats_after_delete_stats_v1');

		return $result;

	}

	/**
	 * Returns the proper table name for Multisies
	 *
	 * @access public
	 * @param mixed $table_name
	 * @return void
	 */
	function get_table_name() {

		global $wpdb;

		return apply_filters('nnr_stats_get_table_name_v1', $wpdb->prefix . $this->table_name);
	}

}

// Add actions for tracking stats

add_action( 'wp_ajax_nnr_stats_record_impression_v1', 			'nnr_stats_record_impresssion_v1');
add_action( 'wp_ajax_nopriv_nnr_stats_record_impression_v1', 	'nnr_stats_record_impresssion_v1');
add_action( 'wp_ajax_nnr_stats_record_conversion_v1', 			'nnr_stats_ajax_record_conversion_v1');
add_action( 'wp_ajax_nopriv_nnr_stats_record_conversion_v1', 	'nnr_stats_ajax_record_conversion_v1');
add_filter( 'nnr_news_int_submission_success_v1', 				'nnr_stats_record_conversion_v1', 10, 1);

/**
 * Record an impression.  This can be called server side or client side.
 *
 * @access public
 * @param bool $ajax (default: true)
 * @param mixed $data_id (default: null)
 * @return void
 */
function nnr_stats_record_impresssion_v1() {

	do_action('nnr_stats_before_record_impresssion_v1');

	$data_id = $_POST['data_id'];
	$table_name = $_POST['table_name'];

	// Return false if no data is given

	if ( !isset($data_id) ) {
		nnr_stats_return_data_v1('Impression was NOT able to be recored because no Data ID given.');
	}

	// Return false if crawler

	if ( nnr_stats_is_bot_v1() ) {
		nnr_stats_return_data_v1('Impression was NOT able to be recored because a crawler was detected.');
	}

	// Return if user is admin or higher

	if ( current_user_can('edit_published_posts') ) {
		nnr_stats_return_data_v1('Impression was NOT able to be recored because current user is an admin.');
	}

	global $wpdb;
	$table_name = $wpdb->prefix . $table_name;
	$today = date('Y-m-d');

	// Check if entry already exsits

	$impressions = $wpdb->query($wpdb->prepare('SELECT * FROM ' . $table_name . ' WHERE `date` = %s AND `data_id` = %d', $today, $data_id));

	// Entry aleady exists, just add 1

	if ( isset($impressions) && $impressions != 0 ) {

		$result = $wpdb->query($wpdb->prepare('UPDATE ' . $table_name . ' SET
			`impressions` = `impressions` + 1
			WHERE `date` = %s AND `data_id` = %d', $today, $data_id
		));

	}

	// Entry does not exist, create a new one and set impressions to 1

	else {

		$result = $wpdb->query($wpdb->prepare('INSERT INTO ' . $table_name . ' (
			`date`,
			`data_id`,
			`impressions`,
			`conversions`
			) VALUES (%s, %d, 1, 0)',
			$today,
			$data_id
		));

	}

	do_action('nnr_stats_after_record_impresssion_v1');

	if ( $result ) {
		nnr_stats_return_data_v1('Impression was able to be recored.');
	} else {
		nnr_stats_return_data_v1('Impression was NOT able to be recored.');
	}
}

/**
 * Record an conversion.  This can be called server side or client side.
 *
 * @access public
 * @param bool $ajax (default: true)
 * @param mixed $data_id (default: null)
 * @return void
 */
function nnr_stats_record_conversion_v1( $data ) {

	do_action('nnr_stats_before_record_conversion_v1');

	global $wpdb;

	$data_id = $data['data_id'];
	$table_name = $data['table_name'];

	$table_name = $wpdb->prefix . $table_name;

	// Return if no data is given

	if ( !isset($data_id) ) {
		return 'Conversion was NOT able to be recored because the Data ID was not retrieved.';
	}

	// Return if user is admin or higher

	if ( current_user_can('edit_published_posts') ) {
		return 'Conversion was NOT able to be recored because current user is not authorized to log conversions.';
	}

	// Return false if crawler

	if ( nnr_stats_is_bot_v1() ) {
		return 'Conversion was NOT able to be recored because there is no user. A crawler was detected.';
	}

	$today = date('Y-m-d');

	// Check if entry already exsits

	$conversions = $wpdb->query($wpdb->prepare('SELECT * FROM ' . $table_name . ' WHERE `date` = %s AND `data_id` = %d', $today, $data_id));

	// Entry aleady exists, just add 1

	if ( isset($conversions) && $conversions != 0) {

		$result = $wpdb->query($wpdb->prepare('UPDATE ' . $table_name . ' SET
			`conversions` = `conversions` + 1
			WHERE `date` = %s AND `data_id` = %d', $today, $data_id
		));

	}

	// Entry does not exist, create a new one and set impressions to 1

	else {

		$result = $wpdb->query($wpdb->prepare('INSERT INTO ' . $table_name . ' (
			`date`,
			`data_id`,
			`impressions`,
			`conversions`
			) VALUES (%s, %d, 0, 1)',
			$today,
			$data_id
		));
	}

	do_action('nnr_stats_after_record_conversion_v1');

	if ( $result ) {
		return 'Conversion was able to be recored.';
	} else {
		return 'Conversion was NOT able to be recored.';
	}
}

/**
 * Record a conversion via AJAX
 *
 * @access public
 * @return void
 */
function nnr_stats_ajax_record_conversion_v1() {

	do_action('nnr_stats_before_ajax_record_conversion_v1');

	$data = nnr_stats_record_conversion_v1(array(
		'data_id'		=> $_POST['data_id'],
		'table_name'	=> $_POST['table_name'],
	));

	do_action('nnr_stats_after_ajax_record_conversion_v1');

	echo $data;
	die();
}

/**
 * Return data based on if this function is called from AJAX or not
 *
 * @access public
 * @param mixed $data
 * @param bool $ajax
 * @return void
 */
function nnr_stats_return_data_v1( $data, $ajax = true ) {

	echo $data;
	die(); // necessary for WordPress AJAX calls

}

/**
 * Check for BOTs visiting site
 *
 * @access public
 * @static
 * @return void
 */
function nnr_stats_is_bot_v1() {

	if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|spider|crawler|curl|^$/i', $_SERVER['HTTP_USER_AGENT'])) {
		return true;
	} else {
		return false;
	}

}

endif;