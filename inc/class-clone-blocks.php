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
class Clone_Blocks {

	/**
	 * Our intialization method for this class. Registers hooks.
	 *
	 * @return void
	 */
	public function init() {

		$this->register_hooks();

	}//end init()

	/**
	 * Calls our methods to register actions and filters.
	 *
	 * @return void
	 */
	public function register_hooks() {

		$this->register_actions();

	}//end register_hooks()

	/**
	 * Register the actions we need to hook into the WP ecosystem.
	 *
	 * @return void
	 */
	public function register_actions() {

		// When a spoke site adds a hub, grab the reusable blocks from that hub.
		add_action( 'updated_option', array( $this, 'updated_option__clone_hub_blocks_to_spoke' ), 30, 3 );

		// When a new block is added on a hub, push it to the spokes.
		add_action( 'rest_after_insert_wp_block', array( $this, 'rest_after_insert_wp_block__push_block_to_spoke' ), 20, 3 );

		// When a block is edited on the hub, push changes to the spoke.
		add_action( 'rest_after_insert_wp_block', array( $this, 'rest_after_insert_wp_block__edit_block_on_spoke' ), 30, 3 );

	}//end register_actions()


	/**
	 * When a spoke site adds a hub, we clone the reusable blocks from the hub into
	 * the spoke. Each reusable block is a post of post type wp_block. We copy them over
	 * and then register an association as post meta as the post IDs would highly likely
	 * be different. This allows us to update this post in the future when a reusable block
	 * is updated/deleted on the hub.
	 *
	 * @param string $option The option being changed.
	 * @param string $old_value The previous value of the option.
	 * @param string $value The new value of the option.
	 * @return void
	 */
	public function updated_option__clone_hub_blocks_to_spoke( $option, $old_value, $value ) {

		// Ensure we only do this if it's our option that is being saved.
		if ( 'srb_use_as_hubs' !== $option ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		// If no hub is selected then there'll be 1 item in the array that is empty.
		if ( ! is_array( $value ) || ! isset( $value[0] ) || ( 1 === count( $value ) && empty( $value[0] ) ) ) {

			// @TODO Ask what to do here; remove all imported reusable  blocks?
			file_put_contents( WP_CONTENT_DIR . '/debug.log', print_r( array( 'all hubs removed. currently leaving any imported reusable blocks' ), true ), FILE_APPEND );
			return;
		}

		// We have at least one hub. We'll now loop over the hubs to get a list of the reusable
		// blocks available on each hub.
		// Each block post is created on the spoke and post meta is attached to indicate the
		// site ID of the hub this came from and the post ID on the hub.
		$data_to_import = array();

		foreach ( $value as $id => $hub_site_id ) {

			// Get the base URL so we can make a REST request.
			$rest_url = get_rest_url( absint( $hub_site_id ), 'wp/v2/blocks/' );
			$args     = array();

			// Allow us to work locally on self-signed certs.
			if ( defined( 'SRB_DO_NOT_VERIFY_SSL' ) && true === constant( 'SRB_DO_NOT_VERIFY_SSL' ) ) {
				$args['sslverify'] = false;
			}

			$response     = wp_remote_get( esc_url_raw( $rest_url ), $args );
			$api_response = json_decode( wp_remote_retrieve_body( $response ), true );

			// Nothing to get? Move on.
			if ( ! $api_response || ! is_array( $api_response ) ) {
				continue;
			}

			$data_to_import[ $hub_site_id ] = $api_response;

		}

		$helpers = new Helpers();

		// Now loop over $data_to_import which contains all the data to import keyed by the hub site ID.
		foreach ( $data_to_import as $hub_id => $reusable_blocks_json_array ) {

			foreach ( $reusable_blocks_json_array as $uid => $reusable_block ) {

				$new_post_id = $helpers->create_reusable_block_on_spoke( $reusable_block, $hub_id );

				// Now detect if this is a block with media, so we can load that.
				$parsed_blocks = parse_blocks( $reusable_block['content']['raw'] );

				if ( ! is_array( $parsed_blocks ) ) {
					continue;
				}

				foreach ( $parsed_blocks as $pid => $block ) {
					$block_name = $block['blockName'];
				}
			}
		}

	}//end updated_option__clone_hub_blocks_to_spoke()


	/**
	 * When a reusable block is created on a hub, push this to the attached spokes.
	 *
	 * @param WP_Post         $post which post is being edited.
	 * @param WP_REST_Request $request - the full REST Request.
	 * @param bool            $creating True when creating a post, false when updating.
	 * @return void
	 */
	public function rest_after_insert_wp_block__push_block_to_spoke( $post, $request, $creating ) {

		// Only do this operation when creating. We have a separate method for editing.
		if ( 1 !== absint( $creating ) ) {
			return;
		}

		// Which site was the block just created on?
		$hub_id = get_current_blog_id();

		$helpers = new Helpers();

		// Is this site a hub?
		if ( ! $helpers->site_is_a_hub( $hub_id ) ) {
			return;
		}

		// Which spokes are attached to this hub?
		$spoke_sites = $helpers->get_spoke_sites_for_hub( $hub_id );

		if ( ! is_array( $spoke_sites ) || empty( $spoke_sites ) ) {
			return;
		}

		// Create the data we need to create the reusable block on the spoke.
		$reusable_block = $helpers->create_reusable_block_data_from_post_object( $post );

		// This hub has spoke sites. Let's loop over each spoke site and push
		// this new block to each spoke.
		foreach ( $spoke_sites as $id => $spoke_site_id ) {
			switch_to_blog( $spoke_site_id );
			$helpers->create_reusable_block_on_spoke( $reusable_block, $hub_id );
			restore_current_blog();
		}

	}//end rest_after_insert_wp_block__push_block_to_spoke()


	/**
	 * When a block is edited on a hub, we have to push those changes to the spoke sites. The first edit
	 * happens just after a block is created - when the user adds a title. Each block created as a
	 * clone on a spoke has post meta which relates it to the hub which created it (Saves the hub ID and
	 * the post ID of the block on the hub). We then work out which post on the spoke relates to the
	 * block just updated on the hub and then update that post on the spoke with the new edits made on
	 * the hub.
	 *
	 * @param WP_Post         $post which post is being edited.
	 * @param WP_REST_Request $request - the full REST Request.
	 * @param bool            $creating True when creating a post, false when updating.
	 * @return void
	 */
	public function rest_after_insert_wp_block__edit_block_on_spoke( $post, $request, $creating ) {

		if ( 1 === $creating ) {
			return;
		}

		// We're editing an already-created block, so we need to ensure to update the
		// spoke and not create a new one.

		// Which site was the block just edited on?
		$hub_id = get_current_blog_id();

		$helpers = new Helpers();

		// Is this site a hub?
		if ( ! $helpers->site_is_a_hub( $hub_id ) ) {
			return;
		}

		// Which spokes are attached to this hub?
		$spoke_sites = $helpers->get_spoke_sites_for_hub( $hub_id );

		if ( ! is_array( $spoke_sites ) || empty( $spoke_sites ) ) {
			return;
		}

		$edited_block_id = $post->ID;

		// On the spoke, for each block sent from the hub we have 2 pieces of meta.
		// One tells us which hub site ID the block originated, and the second
		// is what post ID on the hub that the block originated.
		foreach ( $spoke_sites as $id => $spoke_site_id ) {

			switch_to_blog( $spoke_site_id );

			global $wpdb;

			$postids_from_hub = $wpdb->get_results(
				$wpdb->prepare(
					"
						SELECT post_id
						FROM $wpdb->postmeta
						WHERE meta_key = %s
						AND meta_value = %d
					",
					sanitize_key( 'srb_from_post' ),
					absint( $edited_block_id )
				)
			);

			if ( ! is_array( $postids_from_hub ) || empty( $postids_from_hub ) ) {
				restore_current_blog();
				return;
			}

			$usable_ids = array();
			foreach ( $postids_from_hub as $oid => $object_with_pid ) {
				$usable_ids[] = $object_with_pid->post_id;
			}

			$csv_of_postids = implode( ',', $usable_ids );

			$post_id_on_spoke = $wpdb->get_var(
				$wpdb->prepare(
					"
						SELECT post_id
						FROM $wpdb->postmeta
						WHERE meta_key = %s
						AND meta_value = %d
						AND post_id IN (%s)
					",
					sanitize_key( 'srb_from_site' ),
					absint( $hub_id ),
					trim( $csv_of_postids )
				)
			);

			if ( ! $post_id_on_spoke ) {
				restore_current_blog();
				return;
			}

			// Now we have the post ID on the spoke for the block which has been edited on the hub.
			// Update that post.
			wp_update_post(
				array(
					'ID'           => $post_id_on_spoke,
					'post_title'   => $post->post_title,
					'post_content' => $post->post_content,
					'post_name'    => $post->post_name,
				)
			);

			restore_current_blog();
		}

	}//end rest_after_insert_wp_block__edit_block_on_spoke()

}//end class
