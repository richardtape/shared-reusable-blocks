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

class Options {

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

		/* Hub Options */
		/* *********** */
		// Register the option to say if a site should act as a hub or not
		add_action( 'admin_init', array( $this, 'admin_init__register_hub_option' ) );

		// When the srb_site_is_hub option is saved, hook in and add/remove blogmeta
		add_action( 'updated_option', array( $this, 'updated_option__srb_site_is_hub' ), 20, 3 );

		/* Spoke Options */
		/* ************* */
		// Register the option to allow a spoke site to add one or more hubs.
		add_action( 'admin_init', array( $this, 'admin_init__register_spoke_hub_choice' ) );

		// When a spoke site chooses (a) hub(s), ensure the hub site knows about it
		add_action( 'updated_option', array( $this, 'updated_option__srb_use_as_hubs' ), 20, 3 );

	}// end register_actions()


	/**
	 * Register our filters.
	 *
	 * @return void
	 */
	public function register_filters() {

	}// end register_filters()


	/**
	 * Register the option to allow admins of the current site to stipulate that this
	 * site will be used as a 'hub' for shared reusable blocks. Option appears in the
	 * settings > writing panel as a checkbox.
	 *
	 * @return void
	 */
	public function admin_init__register_hub_option() {

		// Admins of this site only
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// What is the key used to store our option?
		$option_key = 'srb_site_is_hub';

		// Which setting screen is it shown on?
		$screen = 'writing';

		// Register the field...
		register_setting(
			$screen,
			$option_key,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		// ... and now add it.
		add_settings_field(
			$option_key,
			__( 'Shared Reusable Blocks Hub?', 'srb' ),
			array( $this, 'hub_option_markup' ),
			$screen,
			'default',
			array(
				'label_for' => $option_key,
			)
		);

	}// end admin_init__register_hub_option()

	/**
	 * Outputs the checkbox field for saying a site is a hub
	 *
	 * @return void
	 */
	public function hub_option_markup() {

		$is_hub    = absint( get_option( 'srb_site_is_hub' ) );
		$help_text = __( 'Should the reusable blocks on <em>this</em> site be available on <em>other</em> sites which specifically choose to use this site as a hub? i.e. should this site act as a shared reusable blocks hub?', 'srb' );
		?>

		<input type="checkbox" id="srb_site_is_hub" name="srb_site_is_hub" value="1" <?php checked( 1, $is_hub, true ); ?> />

		<?php
		echo wp_kses_post( $help_text );

	}// end hub_option_markup()


	/**
	 * When the srb_site_is_hub option is saved, we modify the new-in-5.1
	 * wp_blogmeta table to say that this site is (or isn't) a hub site. This
	 * will allow spoke sites to quickly find sites which have announced that they
	 * are hub sites in the network.
	 *
	 * @param string $option
	 * @param mixed $old_value
	 * @param mixed $value
	 * @return void
	 */
	public function updated_option__srb_site_is_hub( $option, $old_value, $value ) {

		// Ensure we only do this if it's our option that is being saved.
		if ( 'srb_site_is_hub' !== $option ) {
			return;
		}

		// Sanitize just to make sure.
		$value = absint( $value );

		// If the new value ($value) is 0, then we should remove from blogmeta.
		// If the new value is 1 then we should add to blogmeta.
		switch ( $value ) {

			case 0:
				delete_site_meta( get_current_blog_id(), 'srb_site_is_hub' );
				break;

			case 1:
				update_site_meta( get_current_blog_id(), 'srb_site_is_hub', 1 );
				break;

		}

	}// end updated_option__srb_site_is_hub()

	/**
	 * Register the option in the settings > writing screen to allow an admin to
	 * select which sites they wish to use as a hub for shared reusable blocks.
	 *
	 * @return void
	 */
	public function admin_init__register_spoke_hub_choice() {

		// Admins of this site only
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check that we have at least one hub registered.
		if ( ! $this->at_least_one_hub_registered() ) {
			return;
		}

		// What is the key used to store our option?
		$option_key = 'srb_use_as_hubs';

		// Which setting screen is it shown on?
		$screen = 'writing';

		// Register the field...
		register_setting(
			$screen,
			$option_key,
			array(
				'type'    => 'string',
				'default' => '',
			)
		);

		// ... and now add it.
		add_settings_field(
			$option_key,
			__( 'Use other site as hub?', 'srb' ),
			array( $this, 'spoke_option_markup' ),
			$screen,
			'default',
			array(
				'label_for' => $option_key,
			)
		);

	}// end admin_init__register_spoke_hub_choice()


	/**
	 * Markup for the option which allows an admin to select other sites to use as
	 * a hub for shared reusable blocks.
	 *
	 * @return void
	 */
	public function spoke_option_markup() {

		$use_hubs  = get_option( 'srb_use_as_hubs' );
		$help_text = __( 'From which hub site(s) would you like to fetch reusable blocks?', 'srb' );

		$helpers   = new Helpers();
		$hub_sites = $helpers->fetch_hub_sites();

		$options = '';

		foreach ( $hub_sites as $id => $blog_id ) {

			$blog_id = absint( $blog_id );

			// Don't do anything if the current blog has set itself up as a hub, too
			if ( absint( get_current_blog_id() ) === $blog_id ) {
				continue;
			}

			$blog_name = get_blog_option( $blog_id, 'blogname', $blog_id );
			$selected  = ( in_array( $blog_id, array_values( $use_hubs ), false ) ) ? 'selected' : '';
			$options  .= "<option value='$blog_id' $selected>$blog_name</option>";
		}

		?>

		<select id="srb_use_as_hubs" name="srb_use_as_hubs[]" multiple>
			<option value=""><?php esc_html_e( '--Please choose at least one hub--', 'srb' ); ?></option>
			<?php echo $options; ?>
		</select>

		<?php
		echo wp_kses_post( $help_text );

	}// end spoke_option_markup()


	/**
	 * On a spoke site, when the 'Use other site as hub' option is saved, and a
	 * hub is selected, we inform the hub site that it is being used by the spoke
	 * site. This will allow updates of shared reusable blocks on the hub to be
	 * pushed to the spokes.
	 *
	 * In the blogmeta table we store the HUB site ID in the blog_id column and
	 * a meta_key of 'is_a_hub_for_site' and the SPOKE site ID in the meta_value.
	 * This means it reads as "7 is a hub site for 3" for example.
	 *
	 * @param [type] $option
	 * @param [type] $old_value
	 * @param [type] $value
	 * @return void
	 */
	public function updated_option__srb_use_as_hubs( $option, $old_value, $value ) {

		// Ensure we only do this if it's our option that is being saved.
		if ( 'srb_use_as_hubs' !== $option ) {
			return;
		}

		$option_key_on_hub = 'is_a_hub_for_site';

		// If no hub is selected then there'll be 1 item in the array that is empty
		if ( ! is_array( $value ) || ! isset( $value[0] ) || ( 1 === count( $value ) && empty( $value[0] ) ) ) {

			// find sites with a 'meta_value' of the current (spoke) site ID and a meta_key of $option_key_on_hub
			$this->remove_spoke_site_from_all_hubs( get_current_blog_id() );
			return;
		}

		// There are hubs selected
		// Remove all existing hubs for this spoke
		$this->remove_spoke_site_from_all_hubs( get_current_blog_id() );

		// And now add back all the ones that have just been asked for.
		foreach ( $value as $id => $hub_site_id ) {
			update_site_meta( $hub_site_id, $option_key_on_hub, get_current_blog_id() );
		}

	}// end updated_option__srb_use_as_hubs()




	/**
	 * A spoke site has updated and chosen to have no hubs. We look in the
	 * blogmeta table and remove all references
	 *
	 * @param int $spoke_site_id
	 * @return void
	 */
	public function remove_spoke_site_from_all_hubs( $spoke_site_id = null ) {

		global $wpdb;

		$meta_key   = 'is_a_hub_for_site';
		$meta_value = absint( $spoke_site_id );

		$wpdb->delete(
			$wpdb->blogmeta,
			array(
				'meta_key'   => $meta_key,
				'meta_value' => $meta_value,
			),
			array(
				'%s',
				'%d',
			)
		);

	}// end remove_spoke_site_from_all_hubs()


	/**
	 * Helper method to determine if there is at least one hub registered.
	 *
	 * @return bool true if one or more hubs registered, false otherwise.
	 */
	public function at_least_one_hub_registered() {

		$helpers = new Helpers();
		$hubs    = $helpers->fetch_hub_sites();

		if ( $hubs && count( $hubs ) >= 1 ) {
			return true;
		}

		return false;

	}// end at_least_one_hub_registered()

}// end class Options
