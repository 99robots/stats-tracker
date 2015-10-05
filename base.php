<?php

// Exit if accessed directly

if ( !defined( 'ABSPATH' ) ) exit;

// Check if class already exists

if (!class_exists("NNR_Stats_Tracker_Base_v1")):

/* ================================================================================
 *
 * Base is the base class for Stats Tracker to help with managing repetitive tasks
 *
 ================================================================================ */

class NNR_Stats_Tracker_Base_v1 {

	/**
	 * Sanitize the input value
	 *
	 * @access public
	 * @param mixed $value
	 * @param mixed $html
	 * @return void
	 */
	function sanitize_value( $value, $html = false ) {
		return apply_filters('nnr_stats_sanitize_value_v1', stripcslashes( sanitize_text_field( $value ) ) );
	}

}

endif;