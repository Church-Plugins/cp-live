<?php

namespace CP_Live\Integrations;

use CP_Live\Admin\Settings;
use CP_Live\Services\Service;

class CP_Locations {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of CP_Locations
	 *
	 * @return CP_Locations
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof CP_Locations ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	public function __construct() {

		add_action( 'cp_live_settings_advanced', [ $this, 'advanced_settings' ] );

		if ( ! Settings::get_advanced( 'cp_locations_enabled', false ) ) {
			return;
		}
		
		add_action( 'save_post_cploc_location', [ $this, 'flush_cache' ] );
		add_filter( 'body_class', [ $this, 'live_body_class' ] );
		add_action( 'cploc_location_meta_details', [ $this, 'location_meta' ], 10, 2 );
		add_action( 'cp_live_check', [ $this, 'check' ] );
		add_action( 'admin_init', [ $this, 'maybe_force_pull' ] );
	}
	
	/**
	 * Legacy function to return the embed from the location regardless of the live status
	 * 
	 * @param $location_id
	 *
	 * @return string
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_location_embed( $location_id = false ) {
		if ( ! $location_id ) {
			$location_id = apply_filters( 'cp_live_video_location_id_default', get_query_var( 'cp_location_id' ) );
		}
		
		$embed = '';
		
		if ( ! $location_id ) {
			return $embed;
		}

		// return the embed from the live service if one exists
		// reverse the array to make the top one the default if none are live
		foreach( array_reverse( cp_live()->services->active ) as $service ) {
			/** @var $service Service */
			
			$service->set_context( $location_id );
			
			$embed = $service->get_embed();
			$is_live = $service->is_live();
			
			$service->set_context();
			
			if ( $is_live ) {
				return $embed;
			}
		}
		
		return $embed;
	}
	
	/**
	 * Determine if the location is live streaming. Return video id if true
	 * 
	 * @param $location_id
	 *
	 * @return false|mixed video id
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function is_location_live( $location_id = false) {
		if ( ! $location_id ) {
			$location_id = apply_filters( 'cp_live_video_location_id_default', get_query_var( 'cp_location_id' ) );
		}
		
		if ( empty( $location_id ) ) {
			return false;
		} 
		
		$is_live = false;
		
		foreach( cp_live()->services->active as $service ) {
			/** @var $service Service */
			$service->set_context( $location_id );
			
			$is_live = $service->is_live();

			// reset context
			$service->set_context();
			
			if ( $is_live ) {
				break;
			}
		}		
		
		return $is_live;
	}

	/**
	 * Add class to body when location is live
	 * 
	 * @param $classes
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function live_body_class( $classes ) {
		if ( ! $location_id = get_query_var( 'cp_location_id' ) ) {
			return $classes;
		} 
		
		$live_class = apply_filters( 'cp_live_location_body_class_is_live', 'cp-location-is-live' );
		$not_live_class = apply_filters( 'cp_live_location_body_class_is_not_live', 'cp-location-not-live' );
		
		$classes[] = self::is_location_live( $location_id ) ? $live_class : $not_live_class;
		
		return $classes;
	}
	
	/**
	 * Check the sites to see if any of them are live
	 * 
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function check() {

		foreach( $this->sites_to_check() as $location_id => $data ) {

			$schedules      = get_post_meta( $location_id, 'schedule_group', true );
			$check_for_live = cp_live()->schedule_is_now( $schedules );
			
			foreach( cp_live()->services->active as $service ) {
				/** @var $service Service */
				$service->set_context( $location_id );
				
				// check live video
				$service->check_live_status();
				
				if ( $check_for_live ) {
					$service->check();

					// if the current schedule is set to force the live status, do so now
					if ( ! empty( $check_for_live['force'] ) ) {
						$service->set_live();
					}
				}

				$service->set_context();
			}
			
		}
	
	}
	
	/**
	 * Delete transient that gets live check
	 * 
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function flush_cache() {
		delete_site_transient( 'cp_sites_to_check' );
	}
	
	/**
	 * Add meta for live stream
	 * 
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function location_meta() {
		if ( ! function_exists( 'cp_locations' ) ) {
			return;
		}
		
		// don't assume post id
		$context = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 'location';
		
		foreach( cp_live()->services->get_active_services() as $service => $details ) {
			$cmb = new_cmb2_box( [
				'id'           => "location_live_{$service}_meta",
				'title'        => sprintf( __( '%s Live', 'cp-live' ), $details['label'] ),
				'object_types' => [ cp_locations()->setup->post_types->locations->post_type ],
				'context'      => 'normal',
				'priority'     => 'high',
				'show_names'   => true,
			] );
			
			cp_live()->services->active[ $service ]->set_context( $context );
			cp_live()->services->active[ $service ]->settings( $cmb, true );
			cp_live()->services->active[ $service ]->set_context();
		}
		
		$cmb = new_cmb2_box( [
			'id'           => "location_live_schedule_meta",
			'title'        => __( 'Live Schedules', 'cp-live' ),
			'object_types' => [ cp_locations()->setup->post_types->locations->post_type ],
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		] );
		
		$cmb->add_field( array(
			'name' => __( 'Force Pull', 'cp-live' ),
			'desc' => __( 'Check this box and save to force a check for a live feed right now. This will also reset the status to Not Live if no live feeds are found.', 'cp-live' ),
			'id'   => 'feed_check',
			'type' => 'checkbox',
		) );

		$cmb->add_field( array(
			'name' => __( 'Live Time Description', 'cp-live' ),
			'desc' => __( 'A description of when this location\'s livestream starts.', 'cp-live' ),
			'id'   => 'live_time_description',
			'type' => 'text',
		) );

		Settings::schedule_fields( $cmb );

	}	
	
	/**
	 * Get the sites to check for a live feed
	 * 
	 * @return array|mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function sites_to_check() {
		if ( ! function_exists( 'cp_locations' ) ) {
			return [];
		}
		
		if ( $sites = get_site_transient( 'cp_sites_to_check' ) ) {
			return $sites;
		}
		
		$sites = [];
		
		do_action( 'cploc_multisite_switch_to_main_site' );

		$locations = \CP_Locations\Models\Location::get_all_locations(true);
		
		foreach( $locations as $location ) {
			// make sure this location has a schedule
			if ( get_post_meta( $location->ID, 'schedule_group', true ) ) {
				$sites[ $location->ID ] = [
					'schedule'    => get_post_meta( $location->ID, 'schedule_group', true ),
				];
			}
		}
		
		do_action( 'cploc_multisite_restore_current_blog');
		
		set_site_transient( 'cp_sites_to_check', $sites );
		return $sites;
	}

	/**
	 * Add settings to the advanced tab
	 * 
	 * @param $cmb
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function advanced_settings( $cmb ) {
		$cmb->add_field( array(
			'name'    => __( 'Enable Location Streams', 'cp-live' ),
			'id'      => 'cp_locations_enabled',
			'desc'    => __( 'Enable this option to give each Location the ability to set their own live stream parameters.', 'cp-live' ),
			'type'    => 'radio_inline',
			'default' => 0,
			'options' => [
				1 => __( 'Enable', 'cp-live' ),
				0 => __( 'Disable', 'cp-live' ),
			]
		) );
	}
	
	/**
	 * Force check of all active services for this location. This also updates the live status to the service status.
	 * 
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function maybe_force_pull() {
		global $pagenow;
		
		if ( ( $pagenow != 'post.php' ) || empty( $_GET['post'] ) ) {
			return;
		}
		
		$location_id = absint( $_GET['post'] );
		
		if ( get_post_type( $location_id ) != 'cploc_location' || ! get_post_meta( $location_id, 'feed_check', true ) ) {
			return;
		}

		update_post_meta( $location_id, 'feed_check', 0 );
		
		add_action( 'admin_notices', function () {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', __( 'Force pull has been triggered.', 'cp-live' ) );
		} );

		foreach ( cp_live()->services->active as $service ) {
			/** @var $service Service */
			$service->set_context( $location_id );

			$service->update( 'is_live', 0 );
			$service->check();
				
			$service->set_context();
		}
	}
		
}