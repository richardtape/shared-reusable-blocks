<?php

namespace SharedReusableBlocks;

/**
 * Shared Reusable Blocks Helpers
 *
 * @package     SharedReusableBlocks
 * @author      Richard Tape
 * @copyright   2019 Richard Tape
 * @license     GPL-2.0+
 */
class Helpers {

	/**
	 * Fetch which sites have registered to be a hub.
	 *
	 * @return array
	 */
	public function fetch_hub_sites() {

		global $wpdb;

		$meta_key   = 'srb_site_is_hub';
		$meta_value = '1';

		$hubs = $wpdb->get_results(
			$wpdb->prepare(
				"
					SELECT blog_id
					FROM $wpdb->blogmeta
					WHERE meta_key = %s
					AND meta_value = %d
				",
				$meta_key,
				$meta_value
			)
		);

		if ( ! $hubs || ! is_array( $hubs ) ) {
			return array();
		}

		$hub_sites = array();

		foreach ( $hubs as $id => $hub ) {
			$hub_sites[] = $hub->blog_id;
		}

		return $hub_sites;

	}//end fetch_hub_sites()


	/**
	 * Determine if a site is a hub or not.
	 *
	 * @param int $site_id Which site to test for b eing a hub.
	 * @return bool True if site is set as a hub, false otherwise.
	 */
	public function site_is_a_hub( $site_id ) {

		$site_id = absint( $site_id );

		$hubs = $this->fetch_hub_sites();

		if ( ! in_array( $site_id, array_values( $hubs ), false ) ) {
			return false;
		}

		return true;

	}//end site_is_a_hub()


	/**
	 * For the passed $hub_id return an array of site IDs for all the spokes
	 * attached to this hub.
	 *
	 * @param int $hub_id The site ID of the site we are retrieving the spokes for.
	 * @return array The site IDs for the spokes attached to the passed $hub_id
	 */
	public function get_spoke_sites_for_hub( $hub_id ) {

		$hub_id = absint( $hub_id );

		global $wpdb;

		$meta_key = 'is_a_hub_for_site';

		$spokes = $wpdb->get_results(
			$wpdb->prepare(
				"
					SELECT meta_value
					FROM $wpdb->blogmeta
					WHERE meta_key = %s
					AND blog_id = %d
				",
				$meta_key,
				$hub_id
			)
		);

		if ( ! $spokes || ! is_array( $spokes ) ) {
			return array();
		}

		$spoke_sites = array();

		foreach ( $spokes as $id => $spoke ) {
			$spoke_sites[] = $spoke->meta_value;
		}

		return $spoke_sites;

	}//end get_spoke_sites_for_hub()

	/**
	 * Create a reusable block post on the hub and relate it to the post on the hub.
	 *
	 * @param array $block_json A JSON array containing the details of the block.
	 * @param int   $hub_id The site ID on which the block is to be created.
	 * @return int New Post ID
	 */
	public function create_reusable_block_on_spoke( $block_json = array(), $hub_id ) {

		// Insert post.
		$post_args = array(
			'post_content' => $block_json['content']['raw'],
			'post_title'   => $block_json['title']['raw'],
			'post_type'    => 'wp_block',
			'post_status'  => $block_json['status'],
		);

		$new_post_id = wp_insert_post( $post_args );

		// Now add meta to show which post ID and Blog ID this came from.
		add_post_meta( $new_post_id, 'srb_from_site', absint( $hub_id ) );
		add_post_meta( $new_post_id, 'srb_from_post', absint( $block_json['id'] ) );

		return absint( $new_post_id );

	}//end create_reusable_block_on_spoke()


	/**
	 * Create the reusable block data we need to create a reusable block
	 * from a WP_Post object.
	 *
	 * @param WP_Post $post The post object from which we create the json object.
	 * @return array
	 */
	public function create_reusable_block_data_from_post_object( $post ) {

		$reusable_block = array(
			'status'  => $post->post_status,
			'id'      => $post->ID,
			'content' => array(
				'raw' => $post->post_content,
			),
			'title'   => array(
				'raw' => $post->post_title,
			),
		);

		return $reusable_block;

	}//end create_reusable_block_data_from_post_object()

}//end class
