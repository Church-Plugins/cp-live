<?php

namespace CP_Live\Setup;

use CP_Live\Admin\Settings;

/**
 * Setup plugin initialization
 */
class _Init {

	/**
	 * @var _Init
	 */
	protected static $_instance;

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
	 * Class constructor
	 *
	 */
	protected function __construct() {
		$this->includes();
		$this->actions();
	}

	/**
	 * Admin init includes
	 *
	 * @return void
	 */
	protected function includes() {}

	protected function actions() {
		add_action( 'init', [ $this, 'register_event' ] );
		
		add_filter( 'cron_schedules', [ $this, 'schedules' ] );
		add_filter( 'body_class', [ $this, 'live_body_class' ] );
	}

	/** Actions ***************************************************/

	public function live_body_class( $classes ) {
//		if ( ! $location_id = get_query_var( 'cp_location_id' ) ) {
//			return $classes;
//		}
//
//		$classes[] = self::is_location_live( $location_id ) ? 'cp-is-live' : 'cp-not-live';

		return $classes;
	}

	public function schedules( $schedules ) {
		$schedules['cp-live-check'] = [
			'interval' => Settings::get_advanced( 'cron_interval', 120 ),
			'display'  => esc_html__( 'Live Video Check - Every 2 Minutes' ),
		];

		return $schedules;
	}

	public function register_event( $request = null ) {
		if ( is_array( $request ) ) {
			return;
		}

		// Only run cron job on Sundays
		if ( ! wp_next_scheduled( 'cp_live_check' ) ) {
			wp_schedule_event( time(), 'cp-live-check', 'cp_live_check' );
		}
	}
}
