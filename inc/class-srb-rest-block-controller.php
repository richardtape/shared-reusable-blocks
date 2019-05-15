<?php

namespace SharedReusableBlocks;

/**
 * Shared Reusable Blocks REST Block Controller class
 * Thanks to @timothyjacobs for the idea to extend this class for perms issues
 * with wp_block post type.
 *
 * @package     SharedReusableBlocks
 * @author      Richard Tape
 * @copyright   2019 Richard Tape
 * @license     GPL-2.0+
 */

class SRB_REST_Block_Controller extends \WP_REST_Posts_Controller {

	/**
	 * Ensures blocks can be read publicly.
	 *
	 * @since 5.0.0
	 *
	 * @param object $post Post object that backs the block.
	 * @return bool Whether the block can be read.
	 */
	public function check_read_permission( $post ) {

		return true;

	}

	/**
	 * Filters a response based on the context defined in the schema.
	 *
	 * @since 5.0.0
	 *
	 * @param array  $data    Response data to fiter.
	 * @param string $context Context defined in the schema.
	 * @return array Filtered response.
	 */
	public function filter_response_by_context( $data, $context ) {
		$data = parent::filter_response_by_context( $data, $context );

		/*
		 * Remove `title.rendered` and `content.rendered` from the response. It
		 * doesn't make sense for a reusable block to have rendered content on its
		 * own, since rendering a block requires it to be inside a post or a page.
		 */
		unset( $data['title']['rendered'] );
		unset( $data['content']['rendered'] );

		return $data;
	}

	/**
	 * Retrieves the block's schema, conforming to JSON Schema.
	 *
	 * @since 5.0.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		/*
		 * Allow all contexts to access `title.raw` and `content.raw`. Clients always
		 * need the raw markup of a reusable block to do anything useful, e.g. parse
		 * it or display it in an editor.
		 */
		$schema['properties']['title']['properties']['raw']['context']   = array( 'view', 'edit' );
		$schema['properties']['content']['properties']['raw']['context'] = array( 'view', 'edit' );

		/*
		 * Remove `title.rendered` and `content.rendered` from the schema. It doesn’t
		 * make sense for a reusable block to have rendered content on its own, since
		 * rendering a block requires it to be inside a post or a page.
		 */
		unset( $schema['properties']['title']['properties']['rendered'] );
		unset( $schema['properties']['content']['properties']['rendered'] );

		return $schema;
	}

}// end class SRB_REST_Block_Controller()
