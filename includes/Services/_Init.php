<?php
namespace CP_Live\Services;

use CP_Live\Admin\Request_Actions;
use CP_Live\Admin\Settings;

/**
 * Provides the global $cp_live object
 *
 * @author costmo
 */
class _Init {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Store the active service instances
	 * 
	 * @array
	 */
	public $active;

	/**
	 * Only make one instance of _Init
	 *
	 * @return _Init
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof _Init ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Class constructor: Add Hooks and Actions
	 *
	 */
	protected function __construct() {
		$this->actions();
	}

	protected function actions() {
		add_action( 'plugins_loaded', [ $this, 'load_services' ] );
		add_action( 'cp_live_check', [ $this, 'check' ] );
		add_filter( 'body_class', [ $this, 'live_body_class' ] );
		add_action( 'admin_init', [ $this, 'maybe_force_pull' ] );
	}
	
	/** Actions Methods **************************************/
	
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
		$live_class = apply_filters( 'cp_live_body_class_is_live', 'cp-is-live' );
		$not_live_class = apply_filters( 'cp_live_body_class_is_not_live', 'cp-not-live' );
		
		$classes[] = cp_live()->is_live() ? $live_class : $not_live_class;
		
		return $classes;
	}
	
	/**
	 * Check schedules and trigger service live checks.
	 *
	 * Adds capability and nonce checks to admin request actions. Scheduled
	 * cron calls this hook directly and is unchanged.
	 *
	 * @param array|null $request Request vars for an admin request action.
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function check( $request = null ) {
		if ( ! Request_Actions::is_allowed( Request_Actions::LIVE_CHECK, $request ) ) {
			return;
		}

		$check_for_live = cp_live()->schedule_is_now();
		
		foreach ( $this->active as $service ) {
			/** @var $service Service */

			// check live video
			$service->check_live_status();

			if ( $check_for_live ) {
				$service->check();

				// if the current schedule is set to force the live status, do so now
				if ( ! empty( $check_for_live['force'] ) ) {
					$service->set_live();
				}
			}

		}

	}

	/**
	 * Force check of all active services. This also updates the live status to the service status.
	 * 
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function maybe_force_pull( $request = null ) {
		if ( is_array( $request ) ) {
			return;
		}

		if ( ! Settings::get_advanced( 'feed_check', false ) ) {
			return;
		}

		Settings::update_advanced( 'feed_check', 0 );
		
		add_action( 'admin_notices', function () {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', __( 'Force pull has been triggered.', 'cp-live' ) );
		} );

		foreach ( $this->active as $service ) {
			/** @var $service Service */
			$service->update( 'is_live', 0 );
			$service->check();
		}
		
	}
	
	/**
	 * Return all available services
	 * 
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_available_services() {
		return apply_filters( 'cp_live_available_services', [
			'youtube' => [ 'label' => 'YouTube', 'class' => YouTube::class, 'enabled' => 1 ],
			'resi'    => [ 'label' => 'Resi', 'class' => Resi::class, 'enabled' => 0 ],
		] );
	}

	/**
	 * Return all active services
	 * 
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_active_services() {
		$services = [];
		
		foreach( $this->get_available_services() as $service => $data ) {
			if ( Settings::get_advanced( $service . '_enabled', $data['enabled'] ) ) {
				$services[ $service ] = $data;
			}
		}
		
		return apply_filters( 'cp_live_active_services', $services );
	}

	/**
	 * Load the active services
	 * 
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function load_services( $request = null ) {
		if ( is_array( $request ) ) {
			return;
		}

		foreach( $this->get_active_services() as $service => $data ) {
			$this->active[ $service ] = $data['class']::get_instance();
		}
	}
	
}
