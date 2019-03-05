<?php

namespace SharedReusableBlocks;

use \SharedReusableBlocks\Helpers as Helpers;

/**
 * Shared Reusable Blocks Admin Options class
 *
 * @package     SharedReusableBlocks
 * @author      Richard Tape
 * @copyright   2019 Richard Tape
 * @license     GPL-2.0+
 */

class Rest_API {

	/**
	 * Our intialization method for this class. Registers hooks.
	 *
	 * @return void
	 */
	public function init() {

		$this->register_hooks();

	}// end init()

	/**
	 * Calls our methods to register actions and filters.
	 *
	 * @return void
	 */
	public function register_hooks() {

		$this->register_actions();
		$this->register_filters();

	}// end register_hooks()

	/**
	 * Register the actions we need to hook into the WP ecosystem.
	 *
	 * @return void
	 */
	public function register_actions() {

	}// end register_actions()

	/**
	 * Register our filters.
	 *
	 * @return void
	 */
	public function register_filters() {

		add_filter( 'register_post_type_args', array( $this, 'register_post_type_args__add_reusable_blocks_to_rest_api' ), 99, 2 );

	}// end register_filters()


	/**
	 * Add reusable blocks to the rest API. We do this by registering a new class
	 * to handle the REST API responses and ensuring we give non-logged-in users the
	 * ability to read them.
	 *
	 * @return void
	 */
	public function register_post_type_args__add_reusable_blocks_to_rest_api( $args, $post_type ) {

		if ( 'wp_block' !== $post_type ) {
			return $args;
		}

		$helpers = new Helpers();

		// Check that this site is a hub and only add reusable blocks if it is.
		$hubs = $helpers->fetch_hub_sites();

		if ( ! in_array( get_current_blog_id(), $hubs, false ) ) {
			return $args;
		}

		$args['rest_controller_class'] = '\SharedReusableBlocks\SRB_REST_Block_Controller';

		return $args;

	}// end register_post_type_args__add_reusable_blocks_to_rest_api()

}// end class Rest_API
