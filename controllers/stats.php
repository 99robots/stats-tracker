<?php

// Exit if accessed directly

if ( !defined( 'ABSPATH' ) ) exit;

// Check if class already exists

if ( !class_exists("NNR_Stats_Tracker_Display_v1") ):

/* ================================================================================
 *
 * Stats Tracker is a MVC addon to help you manager all your impressions and
 * converions.
 *
 ================================================================================ */

/**
 * NNR_Stats_Tracker_Display_v1 class.
 */
class NNR_Stats_Tracker_Display_v1 {

	/**
	 * prefix
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access public
	 */
	public $prefix = '';

	/**
	 * text_domain
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access public
	 */
	public $text_domain = '';

	/**
	 * table_name
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access public
	 */
	public $table_name = '';

	/**
	 * data_table_name
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access public
	 */
	public $data_table_name = '';

	/**
	 * stats_page
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access public
	 */
	public $stats_page = '';

	/**
	 * Create a new instance
	 *
	 * @access public
	 * @param mixed $table_name
	 * @param array $args (default: array())
	 * @return void
	 */
	function __construct($table_name, $args = array()) {

		$args = array_merge(array(
			'prefix'			=> '',
			'text_domain'		=> '',
			'stats_page'		=> '',
			'data_table_name'	=> '',
		), $args);

		$args = apply_filters('nnr_stats_tracker_display_args', $args );

        global $status, $page;

        $this->table_name = $table_name;
        $this->data_table_name = $args['data_table_name'];
        $this->prefix = $args['prefix'];
        $this->text_domain = $args['text_domain'];
        $this->stats_page = $args['stats_page'];

        $this->include_scripts();

	}

	/**
	 * Include all the scripts necessary for the Stats Page
	 *
	 * @access public
	 * @return void
	 */
	function include_scripts() {

		// Styles

	    wp_enqueue_style('bootstrap-datetimepicker-css', 	plugins_url( 'css/bootstrap-datetimepicker.min.css', dirname(__FILE__)));
	    wp_enqueue_style('bootstrap-sortable-css',			plugins_url( 'css/bootstrap-sortable.css', dirname(__FILE__)));
	    wp_enqueue_style('stats-tracker-css',				plugins_url( 'css/stats.css', dirname(__FILE__)));

	    // Scripts

	    wp_enqueue_script('line-graph-js', 					plugins_url( 'js/Chart.js', dirname(__FILE__)), array('jquery'));
		wp_enqueue_script('bootstrap-sortable-js', 			plugins_url( 'js/bootstrap-sortable.js', dirname(__FILE__)), array('jquery'));
		wp_enqueue_script('bootstrap-moment-js', 			plugins_url( 'js/moment.js', dirname(__FILE__)), array('jquery'));
		wp_enqueue_script('bootstrap-datetimepicker-js', 	plugins_url( 'js/bootstrap-datetimepicker.min.js', dirname(__FILE__)), array('jquery', 'bootstrap-moment-js'));
		wp_enqueue_script('stats-tracker-js', 				plugins_url( 'js/stats.js', dirname(__FILE__)), array('jquery', 'bootstrap-datetimepicker-js', 'bootstrap-sortable-js', 'line-graph-js'));
		wp_localize_script('stats-tracker-js', 'nnr_stats_tracker_data', array(
			'prefix'			=> $this->prefix,
			'table_name'		=> $this->table_name,
			'data_table_name'	=> $this->data_table_name,
			'text_domain'		=> $this->text_domain,
			'stats_page'		=> $this->stats_page,
		));

	}

	/**
	 * Return an array of data with 0's added to days with no stats
	 *
	 * @access public
	 * @param mixed $start_date
	 * @param mixed $end_date
	 * @param mixed $stats
	 * @return void
	 */
	function add_empty_data($start_date, $end_date, $stats) {

		// Make sure we add data with 0 impressions and converions if there is no data for that date

		$datediff = strtotime($end_date) - strtotime($start_date);
		$date_array = array();

		for ($i = 0; $i <= floor($datediff/(60*60*24)); $i++) {
			$date_array[$i] = date('Y-m-d', mktime(0, 0, 0, date("m", strtotime($start_date)), date("d", strtotime($start_date)) + $i, date("Y", strtotime($start_date))));
		}

		foreach ( $date_array as $date ) {

			$check = false;
			foreach ( $stats as $stat ) {
				if ( $stat['date'] == $date ) {
					$check = true;
				}
			}

			if ( !$check ) {
				$stats[] = array(
					'data_id'		=> $stats[0]['data_id'],
					'date'			=> $date,
					'impressions'	=> '0',
					'conversions'	=> '0',
				);
			}

		}

		// Sort array by date

		usort($stats, 'nnr_stats_sort_by_date');

		return $stats;

	}

}

add_action( 'wp_ajax_nnr_stats_tracker_load', 'nnr_stats_tracker_load_v1');

/**
 * Display the Stats
 *
 * @access public
 * @return void
 */
function nnr_stats_tracker_load_v1() {

	// Check for Table Name

	if ( !isset($_POST['table_name']) || empty($_POST['table_name']) ) {
		echo 'No Stats table name found.';
		die();
	}

	// Check for Table Name

	if ( !isset($_POST['data_table_name']) || empty($_POST['data_table_name']) ) {
		echo 'No Data Manager table name found.';
		die();
	}

	// Check for Text Domain

	if ( !isset($_POST['text_domain']) || empty($_POST['text_domain']) ) {
		$_POST['text_domain'] = '';
	}

	// Check for Stats Page

	if ( !isset($_POST['stats_page']) || empty($_POST['stats_page']) ) {
		$_POST['stats_page'] = '';
	}

	// Check for Text Domain

	if ( !isset($_POST['prefix']) || empty($_POST['prefix']) ) {
		$_POST['prefix'] = '';
	}

	$pie_chart_colors = array(
		'#00a0b0',
		'#edc951',
		'#eb6841',
		'#4f372d',
		'#cc2a36',
	);

	$stats_tracker = new NNR_Stats_Tracker_v1($_POST['table_name']);
	$data_manager = new NNR_Data_Manager_v1($_POST['data_table_name']);

	// Get Start and End Dates

	$start_date = date('Y-m-d', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
	$end_date = date('Y-m-d', strtotime(current_time('mysql')));

	$post_start_date = urldecode($_POST['start_date']);
	$post_end_date = urldecode($_POST['end_date']);

	if (isset($post_start_date) && $post_start_date != '') {
		$start_date = date('Y-m-d', strtotime($post_start_date));
	}

	if (isset($post_end_date) && $post_end_date != '') {
		$end_date = date('Y-m-d', strtotime($post_end_date));
	}

	global $wpdb;

	// All optin_fire stats

	if (isset($_POST['data_id']) && $_POST['data_id'] == 'false') {

		$data = $data_manager->get_data();
		$stats = $stats_tracker->get_stats($start_date, $end_date);
		$data_stats = array();

		foreach ($data as $item) {
			foreach ($stats as $stat) {

				if ( $item['id'] == $stat['data_id'] ) {

					$data_stats[$item['id']] = array(
						'data_id'			=> $stat['data_id'],
						'name'				=> $item['name'],
						'date'				=> $stat['date'],
						'impressions'		=> isset($data_stats[$item['id']]['impressions']) ?
											   $data_stats[$item['id']]['impressions'] + $stat['impressions'] :
											   $stat['impressions'],
						'conversions'		=> isset($data_stats[$item['id']]['conversions']) ?
											   $data_stats[$item['id']]['conversions'] + $stat['conversions'] :
											   $stat['conversions'],
					);
				}
			}
		}

		$impressions_total = 0;
		$conversions_total = 0;

		foreach ($data_stats as $data_stat) {
		    $impressions_total += $data_stat['impressions'];
		    $conversions_total += $data_stat['conversions'];
		}

		$ctr = $impressions_total != 0 ? round(($conversions_total/$impressions_total) * 100,2) : 0;

		$stats_content = '
		<div class="nnr-stats-total-container col-sm-12">
			<div class="nnr-stats-totals col-sm-4">
				<div class="nnr-stats-impressions-total">
					<span>' . __('Impressions', $_POST['text_domain']) . '</span>
					<div class="nnr-stats-totals-text">' . number_format($impressions_total) . '</div>
					<i class="fa fa-globe"></i>
				</div>
			</div>
			<div class="nnr-stats-totals col-sm-4">
				<div class="nnr-stats-conversions-total">
					<span>' . __('Conversions', $_POST['text_domain']) . '</span>
					<div class="nnr-stats-totals-text">' . number_format($conversions_total) . '</div>
					<i class="fa fa-certificate"></i>
				</div>
			</div>
			<div class="nnr-stats-totals col-sm-4">
				<div class="nnr-stats-conversion-rate-total">
					<span>' .  __('Conversion Rate', $_POST['text_domain']) . '</span>
					<div class="nnr-stats-totals-text">' . $ctr . '%</div>
					<i class="fa fa-usd"></i>
				</div>
			</div>
		</div>

		<div class="clearfix"></div>

		<div class="col-sm-6 nnr-stats-table-container">

			<h3 id="nnr-heading" class="text-center">' . __('Total Stats', $_POST['text_domain']) . '</h3>

			<table class="nnr-stats-table table sortable">

				<thead>
					<tr>
						<th>' . __('Name', $_POST['text_domain']) . '</th>
						<th class="nnr-impressions-key">' . __('Impressions', $_POST['text_domain']) . '</th>
						<th class="nnr-conversions-key">' . __('Conversions', $_POST['text_domain']) . '</th>
						<th data-defaultsort="desc" class="nnr-conversion-rate-key">' . __('Conversion Rate', $_POST['text_domain']) . '</th>
					</tr>
				</thead>
				<tbody>';

				if ( count($data_stats) < 1 ) {
					$stats_content .= '<tr><td>' . __('No Stats Data for this time period', $_POST['text_domain']) . '</td></tr>';
				}

				foreach ($data_stats as $data_stat) {

					$ctr = $data_stat['impressions'] > 0 ? round(($data_stat['conversions'] / $data_stat['impressions']) * 100, 2) : '0';

					$stats_content .= '<tr>
						<td data-value="' . $data_stat['name'] . '"><a href="' . admin_url() . 'admin.php?page=' . $_POST['stats_page'] . '&data_id=' . $data_stat['data_id'] . '&data_name=' . $data_stat['name'] . '" class="">' . $data_stat['name'] . '</a></td>
						<td data-value="' . $data_stat['impressions'] . '">' . number_format($data_stat['impressions']) . '</td>';
						$stats_content .= '
						<td data-value="' . $data_stat['conversions'] . '">' . number_format($data_stat['conversions']) . '</td>
						<td data-value="' . $ctr . '" class="conversions column-conversions">' . $ctr . '%</td>
					</tr>';
				}

			$stats_content .= '</tbody>
			</table>
		</div>

		<div class="nnr-conversion-chart col-sm-6">

			<h3 id="nnr-heading" class="text-center">' . __('Conversion Distribution', $_POST['text_domain']) . '</h3>

			<div class="col-sm-12">
				<canvas id="nnr-pie-chart"></canvas>
			</div>

			<ul class="col-sm-12 nnr-pie-chart-legend-colors">';

			$counter = 0;

				foreach ($data_stats as $data_stat) {

					if ( $data_stat['conversions'] < 1 ) {
						continue;
					}

					$stats_content .= '<li class="nnr-pie-chart-legend-item">
						<span class="nnr-pie-chart-legend-color" style="background-color:' . $pie_chart_colors[$counter%count($pie_chart_colors)] . '"></span>
						<span class="nnr-pie-chart-optin-name">' . $data_stat['name'] . '</span>
					</li>';

					$counter++;
				}

			$stats_content .= '</ul>
		</div>';
	}

	// Single optin stats

	else if (isset($_POST['data_id']) && $_POST['data_id'] != 'false') {

		$data_stats = $stats_tracker->get_stats_from_id($_POST['data_id'], $start_date, $end_date);
		$data = $data_manager->get_data_from_id($_POST['data_id']);

		$stats_display = new NNR_Stats_Tracker_Display_v1('');
		$data_stats = $stats_display->add_empty_data($start_date, $end_date, $data_stats);

		// Unset index

		foreach ($data_stats as $key => $data_stat) {
			unset($data_stats[$key]['id']);
			unset($data_stats[$key]['data_id']);
		}

		$impressions_total = 0;
		$conversions_total = 0;
		foreach ($data_stats as $data_stat) {
		    $impressions_total += $data_stat['impressions'];
		    $conversions_total += $data_stat['conversions'];
		}

		$ctr = $impressions_total != 0 ? round(($conversions_total/$impressions_total) * 100,2) : 0;

		$stats_content = '<div class="nnr-stats-total-container col-sm-12">
			<div class="nnr-stats-totals col-sm-4">
				<div class="nnr-stats-impressions-total">
					<span>' . __('Impressions', $_POST['text_domain']) . '</span>
					<div class="nnr-stats-totals-text">' . number_format($impressions_total) . '</div>
					<i class="fa fa-globe"></i>
				</div>
			</div>
			<div class="nnr-stats-totals col-sm-4">
				<div class="nnr-stats-conversions-total">
					<span>' . __('Conversions', $_POST['text_domain']) . '</span>
					<div class="nnr-stats-totals-text">' . number_format($conversions_total) . '</div>
					<i class="fa fa-certificate"></i>
				</div>
			</div>
			<div class="nnr-stats-totals col-sm-4">
				<div class="nnr-stats-conversion-rate-total">
					<span>' .  __('Conversion Rate', $_POST['text_domain']) . '</span>
					<div class="nnr-stats-totals-text">' . $ctr . '%</div>
					<i class="fa fa-usd"></i>
				</div>
			</div>
		</div>

		<div class="clearfix"></div>

		<div class="nnr-line-graph-container">

			<h3 id="nnr-heading" class="text-center">' . __('Impressions and Conversions', $_POST['text_domain']) . '</h3>

			<canvas id="nnr-line-graph"></canvas>
		</div>

		<div class="col-sm-12">

			<h3 id="nnr-heading" class="text-center">' . __('Total Stats', $_POST['text_domain']) . '</h3>

			<table class="table table-responsive sortable">

				<thead>
					<th data-defaultsort="desc">' . __('Date', $_POST['text_domain']) . '</th>
					<th class="nnr-impressions-key">' . __('Impressions', $_POST['text_domain']) . '</th>
					<th class="nnr-conversions-key">' . __('Conversions', $_POST['text_domain']) . '</th>
					<th class="nnr-conversion-rate-key">' . __('Conversion Rate', $_POST['text_domain']) . '</th>
				</thead>';

				foreach (array_reverse($data_stats) as $data_stat) {

					$ctr = $data_stat['impressions'] > 0 ? round(($data_stat['conversions'] / $data_stat['impressions']) * 100, 2) : '0';

					$stats_content .= '<tr>
						<td data-value="' . strtotime($data_stat['date']) . '">' . date('M j Y', strtotime($data_stat['date'])) . '</td>
						<td data-value="' . $data_stat['impressions'] . '">' . number_format($data_stat['impressions']) . '</td>
						<td data-value="' . $data_stat['conversions'] . '">' . number_format($data_stat['conversions']) . '</td>
						<td data-value="' . $ctr . '" class="conversions column-conversions">' . $ctr . '%</td>';

					$stats_content .= '</tr>';
				}

			$stats_content .= '</table>
		</div>';
	}

	echo json_encode(array('stats_content' => $stats_content, 'data_stats' => $data_stats));

	die(); // this is required to terminate immediately and return a proper response
}

/**
 * Sort all data stats by date
 *
 * @access public
 * @param mixed $a
 * @param mixed $b
 * @return void
 */
function nnr_stats_sort_by_date($a, $b) {
	return strtotime($a['date']) - strtotime($b['date']);
}

endif;