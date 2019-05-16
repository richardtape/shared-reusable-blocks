<?php

namespace SharedReusableBlocks;

/**
 * Shared Reusable Blocks
 *
 * @package     SharedReusableBlocks
 * @author      Richard Tape
 * @copyright   2019 Richard Tape
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Shared Reusable Blocks
 * Plugin URI:  https://richardtape.com/shared-reusable-blocks/
 * Description: Allow your sites in a multisite network to share reusable blocks in a hub-and-spoke style.
 * Version:     0.0.1
 * Author:      Richard Tape
 * Author URI:  https://richardtape.com/
 * Text Domain: shared-reusable-blocks
 * Requires PHP:7.0
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */


add_action( 'plugins_loaded', '\SharedReusableBlocks\load_srb_files' );

/**
 * Our startup function
 *
 * @return void
 */
function load_srb_files() {

	require_once 'inc/class-helpers.php';

	require_once 'inc/class-options.php';
	$options = new \SharedReusableBlocks\Options();
	$options->init();

	require_once 'inc/class-clone-blocks.php';
	$clone_blocks = new \SharedReusableBlocks\Clone_Blocks();
	$clone_blocks->init();

	if ( ! srb_is_rest() ) {
		return;
	}

	require_once 'inc/class-rest-api.php';
	require_once 'inc/class-srb-rest-block-controller.php';
	$rest_api = new \SharedReusableBlocks\Rest_API();
	$rest_api->init();

}//end load_srb_files()

/**
 * Checks if the current request is a WP REST API request.
 *
 * Case #1: After WP_REST_Request initialisation
 * Case #2: Support "plain" permalink settings
 * Case #3: URL Path begins with wp-json/ (your REST prefix)
 *          Also supports WP installations in subfolders
 *
 * @return boolean
 * @author matzeeable ref https://wordpress.stackexchange.com/a/317041
 */
function srb_is_rest() {

	$prefix = rest_get_url_prefix();

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST || isset( $_GET['rest_route'] ) && strpos( trim( $_GET['rest_route'], '\\/' ), $prefix, 0 ) === 0 ) {
		return true;
	}

	// (#3)
	$rest_url    = wp_parse_url( site_url( $prefix ) );
	$current_url = wp_parse_url( add_query_arg( array() ) );

	return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;

}//end srb_is_rest()

