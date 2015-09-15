<?php

// Exit if accessed directly

if ( !defined( 'ABSPATH' ) ) exit;

// Check if class already exists

if (!class_exists("NNR_Stats_Tracker_v1")):

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
	 * date_format
	 *
	 * (default value: 'Y-m-d')
	 *
	 * @var string
	 * @access public
	 */
	public $date_format = 'Y-m-d';

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

		$this->table_name = $table_name;

		// Add actions for tracking stats

		add_action( 'wp_ajax_nnr_stats_record_impression', 			array($this, 'record_impresssion'));
		add_action( 'wp_ajax_nopriv_nnr_stats_record_impression', 	array($this, 'record_impresssion'));
		add_action( 'wp_ajax_nnr_stats_record_click', 				array($this, 'record_conversion'));
		add_action( 'wp_ajax_nopriv_nnr_stats_record_click', 		array($this, 'record_conversion'));
	}

	/**
	 * Create the table
	 *
	 * @access public
	 * @param mixed $table_name
	 * @return void
	 */
	function create_table() {

		global $wpdb;

		$result = $wpdb->query("
			CREATE TABLE IF NOT EXISTS `" . $this->get_table_name() . "` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`data_id` int(11) NOT NULL DEFAULT 0,
				`date` date NOT NULL,
				`impressions` int(8) NOT NULL DEFAULT 0,
				`conversions` int(8) NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
		");

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
	function get_stats($start, $end, $id = null, $select = '*') {

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

		$result = $wpdb->get_results($query, 'ARRAY_A');

		return $result;
	}

	/**
	 * Record an impression.  This can be called server side or client side.
	 *
	 * @access public
	 * @param bool $ajax (default: true)
	 * @param mixed $data_id (default: null)
	 * @return void
	 */
	function record_impresssion( $ajax = true, $data_id = null ) {

		if ( $ajax ) {
			$data_id = $_POST['data_id'];
		}

		// Return false if no data is given

		if ( !isset($data_id) ) {
			$this->return_data('Impression was NOT able to be recored because no Data ID given.', $ajax);
		}

		// Return false if crawler

		if ( $this->is_bot() ) {
			$this->return_data('Impression was NOT able to be recored because a crawler was detected.', $ajax);
		}

		// Return if user is admin or higher

		if ( current_user_can('edit_published_posts') ) {
			$this->return_data('Impression was NOT able to be recored because current user is an admin.', $ajax);
		}

		global $wpdb;
		$today = date($this->date_format);

		// Check if entry already exsits

		$impressions = $wpdb->query($wpdb->prepare('SELECT * FROM ' . $this->get_table_name() . ' WHERE `date` = %s AND `data_id` = %d', $today, $data_id));

		// Entry aleady exists, just add 1

		if ( isset($impressions) && $impressions != 0 ) {

			$result = $wpdb->query($wpdb->prepare('UPDATE ' . $this->get_table_name() . ' SET
				`impressions` = `impressions` + 1
				WHERE `date` = %s AND `data_id` = %d', $today, $data_id
			));

		}

		// Entry does not exist, create a new one and set impressions to 1

		else {

			$result = $wpdb->query($wpdb->prepare('INSERT INTO ' . $this->get_table_name() . ' (
				`date`,
				`data_id`,
				`impressions`,
				`conversions`
				) VALUES (%s, %d, 1, 0)',
				$today,
				$data_id
			));

		}

		if ( $result ) {
			$this->return_data('Impression was able to be recored.', $ajax);
		} else {
			$this->return_data('Impression was NOT able to be recored.', $ajax);
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
	function record_conversion( $ajax = true, $data_id = null ) {

		if ( $ajax ) {
			$data_id = isset($_POST['data_id']) ? $_POST['data_id'] : null;
		}

		// Return if no data is given

		if ( !isset($data_id) ) {
			$this->return_data('Conversion was NOT able to be recored because the Data ID was not retrieved.', $ajax);
		}

		// Return if user is admin or higher

		if ( current_user_can('edit_published_posts') ) {
			$this->return_data('Conversion was NOT able to be recored because current user is not authorized to log conversions.', $ajax);
		}

		// Return false if crawler

		if ( $this->is_bot() ) {
			$this->return_data('Conversion was NOT able to be recored because there is no user. A crawler was detected.', $ajax);
		}

		global $wpdb;
		$today = date($this->date_format);

		// Check if entry already exsits

		$conversions = $wpdb->query($wpdb->prepare('SELECT * FROM ' . $this->get_table_name() . ' WHERE `date` = %s AND `data_id` = %d', $today, $data_id));

		// Entry aleady exists, just add 1

		if ( isset($conversions) && $conversions != 0) {

			$result = $wpdb->query($wpdb->prepare('UPDATE ' . $this->get_table_name() . ' SET
				`conversions` = `conversions` + 1
				WHERE `date` = %s AND `data_id` = %d', $today, $data_id
			));

		}

		// Entry does not exist, create a new one and set impressions to 1

		else {

			$result = $wpdb->query($wpdb->prepare('INSERT INTO ' . $this->get_table_name() . ' (
				`date`,
				`data_id`,
				`impressions`,
				`conversions`
				) VALUES (%s, %d, 0, 1)',
				$today,
				$data_id
			));
		}

		if ( $result ) {
			$this->return_data('Conversion was able to be recored.', $ajax);
		} else {
			$this->return_data('Conversion was NOT able to be recored.', $ajax);
		}
	}

	/**
	 * Deletes the stats for a data instance
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	function delete_stats( $data_id ) {

		// Return false if no data id given

		if ( !isset($data_id) ) {
			return false;
		}

		global $wpdb;
		$result = $wpdb->query($wpdb->prepare('DELETE FROM ' . $this->getTableName() . ' WHERE `data_id` = %d', $data_id));

		return $result;

	}

	/**
	 * Check for BOTs visiting site
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	function is_bot() {

		if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|spider|crawler|curl|^$/i', $_SERVER['HTTP_USER_AGENT'])) {
			return true;
		} else {
			return false;
		}

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

		return $wpdb->prefix . $this->table_name;
	}

	/**
	 * Return data based on if this function is called from AJAX or not
	 *
	 * @access public
	 * @param mixed $data
	 * @param bool $ajax
	 * @return void
	 */
	function return_data( $data, $ajax ) {

		if ( $ajax ) {
			echo $data;
			die(); // necessary for WordPress AJAX calls
		} else {
			return $data;
		}

	}

}

endif;