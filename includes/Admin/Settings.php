<?php

namespace CP_Live\Admin;

/**
 * Plugin settings
 *
 */
class Settings {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of \CP_Live\Settings
	 *
	 * @return Settings
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Settings ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get a value from the options table
	 *
	 * @param $key
	 * @param $default
	 * @param $group
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get( $key, $default = '', $group = 'cp_live_main_options' ) {
		$options = get_option( $group, [] );

		if ( isset( $options[ $key ] ) ) {
			$value = $options[ $key ];
		} else {
			$value = $default;
		}

		return apply_filters( 'cp_live_settings_get', $value, $key, $group );
	}

	/**
	 * Get service options
	 *
	 * @param $key
	 * @param $service
	 * @param $default
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_service( $key, $service, $default = '' ) {
		return self::get( $key, $default, "cp_live_{$service}_options" );
	}

	/**
	 * Get Advanced option
	 *
	 * @param $key
	 * @param $default
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_advanced( $key, $default = '' ) {
		return self::get( $key, $default, 'cp_live_advanced_options' );
	}

	/**
	 * Update a value in the options table
	 *
	 * @param $key
	 * @param $value
	 * @param $group
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function update( $key, $value, $group = 'cp_live_main_options' ) {
		$options = get_option( $group, [] );

		$options[ $key ] = apply_filters( 'cp_live_settings_update', $value, $key, $group );

		return update_option( $group, $options );
	}

	/**
	 * Update service options
	 *
	 * @param $key
	 * @param $service
	 * @param $value
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function update_service( $key, $service, $value ) {
		return self::update( $key, $value, "cp_live_{$service}_options" );
	}

	/**
	 * Update Advanced option
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function update_advanced( $key, $value ) {
		return self::update( $key, $value, 'cp_live_advanced_options' );
	}

	/**
	 * Class constructor. Add admin hooks and actions
	 *
	 */
	protected function __construct() {
		add_action( 'cmb2_admin_init', [ $this, 'register_main_options_metabox' ] );
		add_action( 'cmb2_save_options_page_fields', [ $this, 'update_schedule' ] );
	}

	/**
	 * Update the schedule when the options change
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function update_schedule() {
		// the interval may have been changed. Unschedule the hook so that it reschedules with the correct interval
		if ( $check = wp_next_scheduled( 'cp_live_check' ) ) {
			wp_unschedule_event( $check, 'cp-live-check' );
		}
	}

	public function register_main_options_metabox() {

		/**
		 * Registers main options page menu item and form.
		 */
		$args = array(
			'id'           => 'cp_live_main_options_page',
			'title'        => 'CP Live',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cp_live_main_options',
			'tab_group'    => 'cp_live_main_options',
			'tab_title'    => 'Main',
			'parent_slug'  => 'options-general.php',
			'display_cb'   => [ $this, 'options_display_with_tabs'],
		);

		$main_options = new_cmb2_box( $args );

		$main_options->add_field( array(
			'name' => __( 'Display Options', 'cp-live' ),
			'id'   => 'display_options',
			'type' => 'title',
		) );

		$main_options->add_field( array(
			'name'    => __( 'When Not Live', 'cp-live' ),
			'id'      => 'not_live_display',
			'type'    => 'radio',
			'options' => [
				'most_recent' => __( 'Show most recent video', 'cp-live' ),
				'countdown'   => __( 'Show countdown to the next stream', 'cp-live' ),
			],
			'default' => 'most_recent',
		) );

		$main_options->add_field( array(
			'name' => __( 'Live Stream Schedule(s)', 'cp-live' ),
			'id'   => 'live_stream_title',
			'type' => 'title',
		) );

		self::schedule_fields( $main_options );

		foreach( cp_live()->services->get_active_services() as $service => $data ) {
			$box = new_cmb2_box( array(
				'id'           => "cp_live_{$service}_options_page",
				'title'        => 'CP Live Settings',
				'object_types' => array( 'options-page' ),
				'option_key'   => "cp_live_{$service}_options",
				'parent_slug'  => 'cp_live_main_options',
				'tab_group'    => 'cp_live_main_options',
				'tab_title'    => $data['label'],
				'display_cb'   => [ $this, 'options_display_with_tabs' ],
			) );

			cp_live()->services->active[ $service ]->settings( $box );
		}

		$this->advanced_options();
		$this->license_fields();
	}

	protected function license_fields() {
		$license = new \ChurchPlugins\Setup\Admin\License( 'cp_live_license', 424, CP_LIVE_STORE_URL, CP_LIVE_PLUGIN_FILE, get_admin_url( null, 'admin.php?page=cp_live_license' ) );

		/**
		 * Registers settings page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cp_live_license_options_page',
			'title'        => 'CP Live Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cp_live_license',
			'parent_slug'  => 'cp_live_main_options',
			'tab_group'    => 'cp_live_main_options',
			'tab_title'    => 'License',
			'display_cb'   => [ $this, 'options_display_with_tabs' ]
		);

		$options = new_cmb2_box( $args );
		$license->license_field( $options );
	}

	public static function schedule_fields( $cmb2 ) {

		$group_field_id = $cmb2->add_field( array(
			'id'         => 'schedule_group',
			'type'       => 'group',
			'repeatable' => true, // use false if you want non-repeatable group
			'desc'       => __( 'Add schedules to check for a live stream', 'cp-live' ),
			'options'    => array(
				'group_title'   => __( 'Schedule {#}', 'cp-live' ),
				'add_button'    => __( 'Add Another Schedule', 'cp-live' ),
				'remove_button' => __( 'Remove Schedule', 'cp-live' ),
				'sortable'      => false,
			),
		) );

		$cmb2->add_group_field( $group_field_id, array(
			'name'        => __( 'Force Live', 'cp-live' ),
			'desc'        => __( 'Force the status to go live at the designated time even if no live stream is detected.', 'cp-live' ),
			'id'          => 'force',
			'type'        => 'checkbox',
		) );

		$cmb2->add_group_field( $group_field_id, array(
			'name'    => __( 'Day', 'cp-live' ),
			'id'      => 'day',
			'type'    => 'select',
			'options' => [
				'sunday'    => __( 'Sunday', 'cp-live' ),
				'monday'    => __( 'Monday', 'cp-live' ),
				'tuesday'   => __( 'Tuesday', 'cp-live' ),
				'wednesday' => __( 'Wednesday', 'cp-live' ),
				'thursday'  => __( 'Thursday', 'cp-live' ),
				'friday'    => __( 'Friday', 'cp-live' ),
				'saturday'  => __( 'Saturday', 'cp-live' ),
			],
		) );

		$cmb2->add_group_field( $group_field_id, array(
			'name'        => __( 'Time', 'cp-live' ),
			'id'          => 'time',
			'type'        => 'text_time',
			'repeatable'  => true,
			'attributes'  => array(
				'data-timepicker' => json_encode( array(
					'stepMinute' => 1, // 1 minute increments instead of the default 5
				) ),
			),
			'time_format' => 'h:i a',
		) );

	}

	protected function item_options() {
		/**
		 * Registers secondary options page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cpl_item_options_page',
			'title'        => 'CP Live Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cpl_item_options',
			'parent_slug'  => 'cp_live_main_options',
			'tab_group'    => 'cp_live_main_options',
			'tab_title'    => cp_live()->setup->post_types->item->plural_label,
			'display_cb'   => [ $this, 'options_display_with_tabs' ],
		);

		$options = new_cmb2_box( $args );

		$options->add_field( array(
			'name' => __( 'Labels' ),
			'id'   => 'labels',
			'type' => 'title',
		) );

		$options->add_field( array(
			'name'    => __( 'Singular Label', 'cp-live' ),
			'id'      => 'singular_label',
			'type'    => 'text',
			'default' => cp_live()->setup->post_types->item->single_label,
		) );

		$options->add_field( array(
			'name'    => __( 'Plural Label', 'cp-live' ),
			'id'      => 'plural_label',
			'desc'    => __( 'Caution: changing this value will also adjust the url structure and may affect your SEO.', 'cp-live' ),
			'type'    => 'text',
			'default' => cp_live()->setup->post_types->item->plural_label,
		) );

	}

	protected function item_type_options() {
		/**
		 * Registers secondary options page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cpl_item_type_options_page',
			'title'        => 'CP Live Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cpl_item_type_options',
			'parent_slug'  => 'cp_live_main_options',
			'tab_group'    => 'cp_live_main_options',
			'tab_title'    => cp_live()->setup->post_types->item_type->plural_label,
			'display_cb'   => [ $this, 'options_display_with_tabs' ],
		);

		$options = new_cmb2_box( $args );

		$options->add_field( array(
			'name' => __( 'Labels' ),
			'id'   => 'labels',
			'type' => 'title',
		) );

		$options->add_field( array(
			'name'    => __( 'Singular Label', 'cp-live' ),
			'id'      => 'singular_label',
			'type'    => 'text',
			'default' => cp_live()->setup->post_types->item_type->single_label,
		) );

		$options->add_field( array(
			'name'    => __( 'Plural Label', 'cp-live' ),
			'id'      => 'plural_label',
			'desc'    => __( 'Caution: changing this value will also adjust the url structure and may affect your SEO.', 'cp-live' ),
			'type'    => 'text',
			'default' => cp_live()->setup->post_types->item_type->plural_label,
		) );

	}

	protected function speaker_options() {
		/**
		 * Registers secondary options page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cpl_speaker_options_page',
			'title'        => 'CP Live Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cpl_speaker_options',
			'parent_slug'  => 'cp_live_main_options',
			'tab_group'    => 'cp_live_main_options',
			'tab_title'    => cp_live()->setup->post_types->speaker->plural_label,
			'display_cb'   => [ $this, 'options_display_with_tabs' ],
		);

		$options = new_cmb2_box( $args );

		$options->add_field( array(
			'name' => __( 'Labels' ),
			'id'   => 'labels',
			'type' => 'title',
		) );

		$options->add_field( array(
			'name'    => __( 'Singular Label', 'cp-live' ),
			'id'      => 'singular_label',
			'type'    => 'text',
			'default' => cp_live()->setup->post_types->speaker->single_label,
		) );

		$options->add_field( array(
			'name'    => __( 'Plural Label', 'cp-live' ),
			'desc'    => __( 'Caution: changing this value will also adjust the url structure and may affect your SEO.', 'cp-live' ),
			'id'      => 'plural_label',
			'type'    => 'text',
			'default' => cp_live()->setup->post_types->speaker->plural_label,
		) );

	}

	protected function advanced_options() {
		/**
		 * Registers secondary options page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cp_live_advanced_options_page',
			'title'        => 'CP Live Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cp_live_advanced_options',
			'parent_slug'  => 'cp_live_main_options',
			'tab_group'    => 'cp_live_main_options',
			'tab_title'    => 'Advanced',
			'display_cb'   => [ $this, 'options_display_with_tabs' ],
		);

		$advanced_options = new_cmb2_box( $args );

		$advanced_options->add_field( array(
			'name' => __( 'Cron Settings' ),
			'id'   => 'cron_settings',
			'type' => 'title',
		) );

		$advanced_options->add_field( array(
			'name'       => __( 'Request Interval (min)', 'cp-live' ),
			'desc'       => __( 'The number of minutes between each live video check. Some services limit the number of requests, so adjust this number depending on the number of requests your service supports.', 'cp-live' ),
			'id'         => 'cron_interval',
			'type'       => 'text',
			'attributes' => array(
				'type'    => 'number',
				'pattern' => '\d*',
			),
			'default'    => '2',
		) );

		$advanced_options->add_field( array(
			'name'       => __( 'Buffer Before (min)', 'cp-live' ),
			'desc'       => __( 'The number of minutes before the designated time to start checking for a live stream.', 'cp-live' ),
			'id'         => 'buffer_before',
			'type'       => 'text',
			'attributes' => array(
				'type'    => 'number',
				'pattern' => '\d*',
			),
			'default'    => '8',
		) );

		$advanced_options->add_field( array(
			'name'       => __( 'Buffer After (min)', 'cp-live' ),
			'desc'       => __( 'The number of minutes after the designated time to stop checking for a live stream if one has not been found.', 'cp-live' ),
			'id'         => 'buffer_after',
			'type'       => 'text',
			'attributes' => array(
				'type'    => 'number',
				'pattern' => '\d*',
			),
			'default'    => '12',
		) );

		$advanced_options->add_field( [
			'name'        => __( 'Live Video Duration', 'cp-live' ),
			'id'          => 'live_video_duration',
			'type'        => 'text',
			'default'     => '6',
			'description' => __( 'How many hours to show the service as live once the video has started.', 'cp-live' ),
			'attributes'  => array(
				'type'    => 'number',
				'pattern' => '\d*',
			),
		] );

		do_action( 'cp_live_settings_advanced', $advanced_options );

		$advanced_options->add_field( array(
			'name' => __( 'Services' ),
			'id'   => 'services_enabled',
			'type' => 'title',
		) );

		foreach ( cp_live()->services->get_available_services() as $service => $data ) {
			$advanced_options->add_field( array(
				'name'    => sprintf( __( 'Enable %s Live', 'cp-live' ), $data['label'] ),
				'id'      => $service . '_enabled',
				'type'    => 'radio_inline',
				'default' => $data['enabled'],
				'options' => [
					1 => __( 'Enable', 'cp-live' ),
					0 => __( 'Disable', 'cp-live' ),
				]
			) );
		}

		$advanced_options->add_field( array(
			'name' => __( 'Force Pull' ),
			'id'   => 'force_pull_title',
			'type' => 'title',
		) );

		$advanced_options->add_field( array(
			'name' => __( 'Force Pull', 'cp-live' ),
			'desc' => __( 'Check this box and save to force a check for a live feed right now. This will also reset the status to Not Live if no live feeds are found.', 'cp-live' ),
			'id'   => 'feed_check',
			'type' => 'checkbox',
		) );
	}

	/**
	 * A CMB2 options-page display callback override which adds tab navigation among
	 * CMB2 options pages which share this same display callback.
	 *
	 * @param \CMB2_Options_Hookup $cmb_options The CMB2_Options_Hookup object.
	 */
	public function options_display_with_tabs( $cmb_options ) {
		$tabs = $this->options_page_tabs( $cmb_options );
		?>
		<div class="wrap cmb2-options-page option-<?php echo $cmb_options->option_key; ?>">
			<?php if ( get_admin_page_title() ) : ?>
				<h2><?php echo wp_kses_post( get_admin_page_title() ); ?></h2>
			<?php endif; ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $option_key => $tab_title ) : ?>
					<a class="nav-tab<?php if ( isset( $_GET['page'] ) && $option_key === $_GET['page'] ) : ?> nav-tab-active<?php endif; ?>"
					   href="<?php menu_page_url( $option_key ); ?>"><?php echo wp_kses_post( $tab_title ); ?></a>
				<?php endforeach; ?>
			</h2>
			<form class="cmb-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST"
				  id="<?php echo $cmb_options->cmb->cmb_id; ?>" enctype="multipart/form-data"
				  encoding="multipart/form-data">
				<input type="hidden" name="action" value="<?php echo esc_attr( $cmb_options->option_key ); ?>">
				<?php $cmb_options->options_page_metabox(); ?>
				<?php submit_button( esc_attr( $cmb_options->cmb->prop( 'save_button' ) ), 'primary', 'submit-cmb' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Gets navigation tabs array for CMB2 options pages which share the given
	 * display_cb param.
	 *
	 * @param \CMB2_Options_Hookup $cmb_options The CMB2_Options_Hookup object.
	 *
	 * @return array Array of tab information.
	 */
	public function options_page_tabs( $cmb_options ) {
		$tab_group = $cmb_options->cmb->prop( 'tab_group' );
		$tabs      = array();

		foreach ( \CMB2_Boxes::get_all() as $cmb_id => $cmb ) {
			if ( $tab_group === $cmb->prop( 'tab_group' ) ) {
				$tabs[ $cmb->options_page_keys()[0] ] = $cmb->prop( 'tab_title' )
					? $cmb->prop( 'tab_title' )
					: $cmb->prop( 'title' );
			}
		}

		return $tabs;
	}


}
