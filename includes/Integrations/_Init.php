<?php
namespace CP_Live\Integrations;

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
	 * @var CP_Locations
	 */
	public $cp_locations = false;

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
		add_action( 'plugins_loaded', [ $this, 'load_integrations' ] );
	}
	
	/** Actions Methods **************************************/
	
	public function load_integrations( $request = null ) {
		if ( null !== $request ) {
			return;
		}

		if ( function_exists( 'cp_locations' ) ) {
			$this->cp_locations = CP_Locations::get_instance();
		}
		
		do_action( 'cp_live_load_integrations' );
	}

}
