<?php
namespace CP_Live\Services;

use CP_Live\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @author Tanner Moushey
 */
abstract class Service {

	public $id;
	
	/**
	 * @var self
	 */
	protected static $_instance;

	/**
	 * The context for this service actions. Use global for site Settings. Set to ID for post_meta.
	 * 
	 * @var string | integer
	 */
	public $context = 'global';

	/**
	 * Only make one instance of Service
	 *
	 * @return self
	 */
	public static function get_instance() {
		$class = get_called_class();

		if ( ! self::$_instance instanceof $class ) {
			self::$_instance = new $class();
		}

		return self::$_instance;
	}

	/**
	 * Get things started
	 *
	 * @since   1.0
	 */
	protected function __construct() {
		$this->add_actions();
	}

	/**
	 * Default action-adder
	 *
	 * @return void
	 */
	public function add_actions() {
		add_action( 'cp_live_settings', [ $this, 'settings' ] );
	}

	/**
	 * Turn off live status if the service has been live past the live duration
	 * 
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function check_live_status() {
		if ( ! $this->is_live() ) {
			return;
		}
		
		$live_duration = Settings::get_advanced( 'live_video_duration', 6 ) * HOUR_IN_SECONDS;
		
		// if we are beyond the live duration, set the service to not live
		if ( time() > $this->get( 'live_start' ) + $live_duration ) {
			$this->update( 'is_live', 0 );
		}
	}

	/**
	 * Set the live data
	 * 
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function set_live( $force = false ) {
		if ( ! $force && $this->is_live() ) {
			return;
		}
		
		$this->update( 'is_live', 1 );
		$this->update( 'live_start', time() );
		
		do_action( 'cp_live_service_set_live', $this );
	}
	
	abstract function check();

	/**
	 * The settings for this Service
	 * 
	 * @param $cmb \CMB2 The CMB2 object to attach the fields to
	 *
	 * @return void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function settings( $cmb ) {

		// add prefix to fields if we are not in the global context. Other services may use the same id.
		$prefix = 'global' != $this->context ? $this->id . '_' : '';
		
		$cmb->add_field( [
			'name'    => __( 'Channel Status', 'cp-live' ),
			'id'      => $prefix . 'is_live',
			'type'    => 'radio_inline',
			'options' => [ 1 => __( 'Live', 'cp-live' ), 0 => __( 'Not Live', 'cp-live' ) ],
			'default' => 0,
		] );

		$cmb->add_field( [
			'name'    => __( 'Live Start', 'cp-live' ),
			'id'      => $prefix . 'live_start',
			'type'    => 'hidden',
		] );

		// for options pages, make sure we only trigger if we are on the current page
		if ( $cmb->is_options_page_mb() && ! $cmb->doing_options_page() ) {
			return;
		}
		
		add_action( "cmb2_save_field_{$prefix}is_live", [ $this, 'live_override' ], 10, 3 );
	}

	/**
	 * If the user manually marked the feed as live, trigger the associated actions.
	 * 
	 * @param $updated
	 * @param $action
	 * @param $field
	 *
	 * @since  1.0.4
	 *
	 * @author Tanner Moushey
	 */
	public function live_override( $updated, $action, $field ) {
		if ( ! $updated ) {
			return;
		}
		
		if ( isset( $_POST['post_ID'] ) ) {
			$this->set_context( absint( $_POST['post_ID'] ) );
		}
		
		// if the new status is live, trigger corresponding actions
		if ( ! empty( $field->value ) ) {
			
			// add the live_start to the data_to_save so that it saves correctly
			$live_key = 'global' != $this->context ? $this->id . '_live_start' : 'live_start';			
			$field->get_cmb()->data_to_save[ $live_key ] = time();
			
			$this->set_live( true );
		}

		// restore to global context
		$this->set_context();
	}

	/**
	 * Set context
	 * 
	 * @param $context
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function set_context( $context = 'global' ) {
		$this->context = $context;
	}
	
	/**
	 * Determine if the service is live
	 * 
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function is_live() {
		return apply_filters( 'cp_live_service_is_live', $this->get( 'is_live', false ), $this );
	}
	
	/**
	 * Get service setting
	 * 
	 * @param $key
	 * @param $default
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get( $key, $default = '' ) {
		if ( 'global' == $this->context ) {
			$value = Settings::get_service( $key, $this->id, $default );
		} else {
			if ( ! $value = get_post_meta( $this->context, $this->id . '_' . $key, true ) ) {
				$value = $default;
			}
		}
		
		return apply_filters( 'cp_live_service_get', $value, $key, $this->context, $this );
	}

	/**
	 * Update service setting
	 * 
	 * @param $key
	 * @param $value
	 *
	 * @return void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function update( $key, $value ) {
		$value = apply_filters( 'cp_live_service_update', $value, $key, $this->context, $this );
		
		if ( 'global' == $this->context ) {
			Settings::update_service( $key, $this->id, $value );
		} else {
			update_post_meta( $this->context, $this->id . '_' . $key, $value );
		}
	}

	abstract public function get_embed();
}
