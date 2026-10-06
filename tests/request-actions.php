<?php
/**
 * Adds capability and nonce checks to admin request actions.
 *
 * Run from the plugin root:
 *   php tests/request-actions.php
 */

if ( 'cli' !== PHP_SAPI ) {
	fwrite( STDERR, "Run this file with PHP CLI.\n" );
	exit( 1 );
}

$failures = 0;
$checks   = 0;

function expect( $condition, $message ) {
	global $failures, $checks;

	$checks++;

	if ( $condition ) {
		echo "ok {$message}\n";
		return;
	}

	$failures++;
	echo "FAIL {$message}\n";
}

function reset_state() {
	$GLOBALS['cp_live_called']       = false;
	$GLOBALS['service_checked']      = false;
	$GLOBALS['get_post_meta_called'] = false;
	$GLOBALS['test_caps']            = array();
	$GLOBALS['test_doing_cron']      = false;
	$GLOBALS['valid_nonce']          = 'valid-nonce';
	$GLOBALS['valid_nonce_action']   = 'cp_live_check';
	$GLOBALS['test_sites']           = array();
	$_GET                            = array();
	$_POST                           = array();
	$_REQUEST                        = array();
}

function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
	$GLOBALS['test_hooks'][ $hook ][] = $callback;
}

function do_action( $hook ) {
	$args = array_slice( func_get_args(), 1 );

	if ( ! isset( $GLOBALS['dispatched_actions'] ) || ! is_array( $GLOBALS['dispatched_actions'] ) ) {
		$GLOBALS['dispatched_actions'] = array();
	}

	$callbacks = isset( $GLOBALS['test_hooks'][ $hook ] ) ? $GLOBALS['test_hooks'][ $hook ] : array();

	$GLOBALS['dispatched_actions'][] = array(
		'hook'      => $hook,
		'args'      => $args,
		'callbacks' => count( $callbacks ),
	);

	foreach ( $callbacks as $callback ) {
		call_user_func_array( $callback, $args );
	}
}

function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {}

function get_option( $key, $default = false ) {
	return $default;
}

function apply_filters( $tag, $value ) {
	return $value;
}

function current_user_can( $cap ) {
	return ! empty( $GLOBALS['test_caps'][ $cap ] );
}

function wp_verify_nonce( $nonce, $action = -1 ) {
	return isset( $GLOBALS['valid_nonce'], $GLOBALS['valid_nonce_action'] )
		&& $GLOBALS['valid_nonce'] === $nonce
		&& $GLOBALS['valid_nonce_action'] === $action;
}

function wp_doing_cron() {
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return true;
	}

	return ! empty( $GLOBALS['test_doing_cron'] );
}

function sanitize_key( $key ) {
	$key = strtolower( (string) $key );

	return preg_replace( '/[^a-z0-9_\-]/', '', $key );
}

function wp_unslash( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'wp_unslash', $value );
	}

	return is_string( $value ) ? stripslashes( $value ) : $value;
}

function sanitize_text_field( $str ) {
	return is_string( $str ) ? trim( strip_tags( $str ) ) : '';
}

function get_site_transient( $key ) {
	return ! empty( $GLOBALS['test_sites'] ) ? $GLOBALS['test_sites'] : false;
}

function get_post_meta( $post_id, $key = '', $single = false ) {
	$GLOBALS['get_post_meta_called'] = true;

	return array();
}

function cp_locations() {
	return true;
}

function cp_live() {
	$GLOBALS['cp_live_called'] = true;

	return new class {
		public $services;

		public function __construct() {
			$this->services = (object) array( 'active' => array() );
		}

		public function schedule_is_now( $schedules = false ) {
			return false;
		}
	};
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

use CP_Live\Admin\Request_Actions;
use CP_Live\Integrations\CP_Locations;
use CP_Live\Services\_Init as Services_Init;

$services  = Services_Init::get_instance();
$locations = CP_Locations::get_instance();

$services->active = array(
	new class {
		public function check_live_status() {
			$GLOBALS['service_checked'] = true;
		}

		public function check() {
			$GLOBALS['service_checked'] = true;
		}

		public function set_live() {}
	},
);

function ran_check() {
	return ! empty( $GLOBALS['cp_live_called'] ) || ! empty( $GLOBALS['service_checked'] ) || ! empty( $GLOBALS['get_post_meta_called'] );
}

reset_state();
$GLOBALS['test_sites'] = array(
	10 => array( 'schedule' => array() ),
);
$_REQUEST              = array( 'cp_action' => 'cp_live_check' );
$services->check( array( 'cp_action' => 'cp_live_check' ) );
$locations->check( array( 'cp_action' => 'cp_live_check' ) );
expect( ! ran_check(), 'logged-out request does nothing' );

reset_state();
$GLOBALS['test_sites'] = array(
	10 => array( 'schedule' => array() ),
);
$_REQUEST              = array( 'cp_action' => 'cp_live_check' );
$services->check();
$locations->check();
expect( ! ran_check(), 'logged-out direct hook does nothing' );

reset_state();
$GLOBALS['test_caps'] = array( 'manage_options' => true );
$request              = array(
	'cp_action' => 'cp_live_check',
	'_wpnonce'  => 'valid-nonce',
);
$_REQUEST             = $request;
$services->check( $request );
expect( ! empty( $GLOBALS['cp_live_called'] ) && ! empty( $GLOBALS['service_checked'] ), 'admin with a nonce runs the service check' );

reset_state();
$GLOBALS['test_caps']  = array( 'manage_options' => true );
$GLOBALS['test_sites'] = array(
	10 => array( 'schedule' => array() ),
);
$request               = array(
	'cp_action' => 'cp_live_check',
	'_wpnonce'  => 'valid-nonce',
);
$_REQUEST              = $request;
$locations->check( $request );
expect( ! empty( $GLOBALS['cp_live_called'] ) && ! empty( $GLOBALS['get_post_meta_called'] ), 'admin with a nonce runs the location check' );

reset_state();
$GLOBALS['test_caps']  = array( 'manage_options' => true );
$GLOBALS['test_sites'] = array(
	10 => array( 'schedule' => array() ),
);
$services->check( array( 'cp_action' => 'cp_live_check' ) );
$locations->check( array( 'cp_action' => 'cp_live_check' ) );
expect( ! ran_check(), 'admin without a nonce does nothing' );

reset_state();
$GLOBALS['test_doing_cron'] = true;
$services->check();
expect( ! empty( $GLOBALS['cp_live_called'] ) && ! empty( $GLOBALS['service_checked'] ), 'scheduled cron runs the service check' );

reset_state();
$GLOBALS['test_doing_cron'] = true;
$GLOBALS['test_sites']      = array(
	10 => array( 'schedule' => array() ),
);
$locations->check();
expect( ! empty( $GLOBALS['cp_live_called'] ), 'scheduled cron runs the location check' );

reset_state();
$GLOBALS['test_doing_cron'] = true;
$_REQUEST                   = array( 'cp_action' => 'cp_live_check' );
$services->check();
expect( ! empty( $GLOBALS['service_checked'] ), 'scheduled cron still runs when the query contains the action' );

reset_state();
$GLOBALS['test_doing_cron'] = true;
$GLOBALS['test_sites']      = array(
	10 => array( 'schedule' => array() ),
);
$_REQUEST                   = array( 'cp_action' => 'cp_live_check' );
$services->check( array( 'cp_action' => 'cp_live_check' ) );
$locations->check( array( 'cp_action' => 'cp_live_check' ) );
expect( ! ran_check(), 'request during cron without a nonce does nothing' );

reset_state();
expect(
	Request_Actions::is_allowed( Request_Actions::LIVE_CHECK, null ),
	'scheduled hook with no request args is allowed'
);

// Bundled ChurchPlugins\Admin\_Init::request_actions() passes the request
// array into the hook. DOING_CRON is defined and cp_action is set, and the
// check still does nothing because those vars are present.
$core_file = dirname( __DIR__ ) . '/includes/ChurchPlugins/Admin/_Init.php';
if ( ! is_readable( $core_file ) ) {
	fwrite( STDERR, "Bundled ChurchPlugins core is not checked out.\n" );
	exit( 1 );
}
require_once $core_file;

reset_state();
$GLOBALS['test_sites'] = array(
	10 => array( 'schedule' => array() ),
);
if ( ! defined( 'DOING_CRON' ) ) {
	define( 'DOING_CRON', true );
}
$_GET['cp_action']     = 'cp_live_check';
$_REQUEST['cp_action'] = 'cp_live_check';
$GLOBALS['dispatched_actions'] = array();

$core = ( new ReflectionClass( 'ChurchPlugins\\Admin\\_Init' ) )->newInstanceWithoutConstructor();
$core->request_actions();

$dispatch = isset( $GLOBALS['dispatched_actions'][0] ) ? $GLOBALS['dispatched_actions'][0] : array();
expect(
	! empty( $dispatch['callbacks'] )
	&& isset( $dispatch['hook'] ) && 'cp_live_check' === $dispatch['hook']
	&& isset( $dispatch['args'][0]['cp_action'] ) && 'cp_live_check' === $dispatch['args'][0]['cp_action']
	&& ! ran_check(),
	'bundled request dispatch during cron does nothing'
);

if ( $failures > 0 ) {
	fwrite( STDERR, "{$checks} checks, {$failures} failed\n" );
	exit( 1 );
}

echo "{$checks} checks passed\n";
exit( 0 );
