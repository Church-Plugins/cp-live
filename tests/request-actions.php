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
	$GLOBALS['test_options']         = array();
	$GLOBALS['state_changes']        = 0;
	$GLOBALS['schedule_checked']     = false;
	$GLOBALS['locale_loaded']        = false;
	$GLOBALS['plugin_active_called'] = false;
	$GLOBALS['boxes_registered']     = 0;
	$GLOBALS['registered_box_ids']   = array();
	$GLOBALS['scripts_enqueued']     = false;
	$_GET                            = array();
	$_POST                           = array();
	$_REQUEST                        = array();
}

function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
	$GLOBALS['test_hooks'][ $hook ][] = $callback;
}

function do_action( $hook ) {
	$args = array_slice( func_get_args(), 1 );

	// A hook fired with no values is passed an empty string.
	if ( array() === $args ) {
		$args = array( '' );
	}

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
	if ( isset( $GLOBALS['test_options'] ) && is_array( $GLOBALS['test_options'] ) && array_key_exists( $key, $GLOBALS['test_options'] ) ) {
		return $GLOBALS['test_options'][ $key ];
	}

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
			$this->services = new class {
				public $active = array();

				public function get_active_services() {
					return array();
				}

				public function get_available_services() {
					return array(
						'youtube' => array(
							'label'   => 'YouTube',
							'enabled' => 1,
						),
					);
				}
			};
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

$GLOBALS['test_core'] = $core;

function __( $text, $domain = 'default' ) {
	return $text;
}

function esc_html__( $text, $domain = 'default' ) {
	return $text;
}

function absint( $maybeint ) {
	return abs( (int) $maybeint );
}

function update_option( $option, $value ) {
	$GLOBALS['state_changes']++;

	return true;
}

function update_post_meta( $post_id, $meta_key, $meta_value ) {
	$GLOBALS['state_changes']++;

	return true;
}

function delete_site_transient( $transient ) {
	$GLOBALS['state_changes']++;

	return true;
}

function get_template_directory() {
	return '/tmp';
}

function get_template_directory_uri() {
	return 'http://example.test';
}

function trailingslashit( $value ) {
	return rtrim( (string) $value, '/\\' ) . '/';
}

function plugins_url( $path = '', $plugin = '' ) {
	return 'http://example.test/' . $path;
}

function get_user_locale() {
	$GLOBALS['locale_loaded'] = true;

	return 'en_US';
}

function wp_next_scheduled( $hook ) {
	$GLOBALS['schedule_checked'] = true;

	return true;
}

function is_plugin_active( $plugin ) {
	$GLOBALS['plugin_active_called'] = true;

	return true;
}

function add_shortcode( $tag, $callback ) {}

function get_admin_url( $blog_id = null, $path = '' ) {
	return 'http://example.test/wp-admin/' . $path;
}

function wp_parse_args( $args, $defaults = array() ) {
	if ( ! is_array( $args ) ) {
		$args = array();
	}

	return array_merge( $defaults, $args );
}

function wp_register_script() {
	return true;
}

function wp_register_style() {
	return true;
}

function wp_enqueue_script() {
	$GLOBALS['scripts_enqueued'] = true;

	return true;
}

function wp_enqueue_style() {
	$GLOBALS['styles_enqueued'] = true;

	return true;
}

function new_cmb2_box( $args ) {
	$GLOBALS['boxes_registered']++;
	$GLOBALS['registered_box_ids'][] = isset( $args['id'] ) ? $args['id'] : '';

	return new class {
		public function add_field( $field ) {
			return isset( $field['id'] ) ? $field['id'] : '';
		}

		public function add_group_field( $id, $field ) {
			return isset( $field['id'] ) ? $field['id'] : '';
		}
	};
}

function settings_box() {
	return new class {
		public $fields = array();

		public function add_field( $field ) {
			$this->fields[] = $field;

			return isset( $field['id'] ) ? $field['id'] : '';
		}

		public function is_options_page_mb() {
			return false;
		}

		public function doing_options_page() {
			return false;
		}
	};
}

function dispatch_logged_out( $hook ) {
	reset_state();
	$_GET                          = array( 'cp_action' => $hook );
	$_POST                         = array();
	$_REQUEST                      = array( 'cp_action' => $hook );
	$GLOBALS['dispatched_actions'] = array();

	$error = null;

	try {
		$GLOBALS['test_core']->request_actions();
	} catch ( Throwable $e ) {
		$error = $e;
	}

	$dispatch = isset( $GLOBALS['dispatched_actions'][0] ) ? $GLOBALS['dispatched_actions'][0] : array();

	return array( $error, $dispatch );
}

function expect_quiet_dispatch( $hook, $message ) {
	list( $error, $dispatch ) = dispatch_logged_out( $hook );

	if ( null !== $error ) {
		echo 'error ' . $message . ': ' . $error->getMessage() . "\n";
	}

	$callbacks = isset( $dispatch['callbacks'] ) ? $dispatch['callbacks'] : 0;
	$args_ok   = isset( $dispatch['args'][0]['cp_action'] ) && $hook === $dispatch['args'][0]['cp_action'];

	expect(
		null === $error
		&& $callbacks >= 1
		&& $args_ok
		&& empty( $GLOBALS['state_changes'] )
		&& isset( $dispatch['hook'] ) && $hook === $dispatch['hook'],
		$message
	);
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

if ( ! defined( 'CP_LIVE_PLUGIN_VERSION' ) ) {
	define( 'CP_LIVE_PLUGIN_VERSION', '1.1.1' );
}

if ( ! defined( 'CP_LIVE_PLUGIN_FILE' ) ) {
	define( 'CP_LIVE_PLUGIN_FILE', dirname( __DIR__ ) . '/cp-live.php' );
}

if ( ! defined( 'CP_LIVE_STORE_URL' ) ) {
	define( 'CP_LIVE_STORE_URL', 'https://example.test' );
}

if ( ! defined( 'WP_LANG_DIR' ) ) {
	define( 'WP_LANG_DIR', '/tmp/languages' );
}

$services->active = array(
	new class {
		public function check_live_status() {}

		public function check() {
			$GLOBALS['service_checked'] = true;
		}

		public function set_live() {}

		public function update( $key, $value ) {
			$GLOBALS['service_updated'] = true;
		}
	},
);

$youtube = CP_Live\Services\YouTube::get_instance();
$resi    = CP_Live\Services\Resi::get_instance();
$bare    = new class extends CP_Live\Services\Service {
	public $id = 'bare';

	public function __construct() {
		parent::__construct();
	}

	public function check() {}

	public function get_embed() {
		return '';
	}
};

$settings_page = CP_Live\Admin\Settings::get_instance();
$setup         = CP_Live\Setup\_Init::get_instance();
$integrations  = CP_Live\Integrations\_Init::get_instance();
$plugin        = CP_Live\_Init::get_instance();

$plugin_src = file_get_contents( dirname( __DIR__ ) . '/cp-live.php' );
$fn_start   = strpos( $plugin_src, 'function cp_live_load_textdomain' );
$fn_end     = strpos( $plugin_src, 'add_action', $fn_start );
if ( false === $fn_start || false === $fn_end ) {
	fwrite( STDERR, "Could not load the textdomain callback.\n" );
	exit( 1 );
}
eval( substr( $plugin_src, $fn_start, $fn_end - $fn_start ) );
add_action( 'init', 'cp_live_load_textdomain' );

// These hooks are registered when location streams are enabled.
add_action( 'save_post_cploc_location', array( $locations, 'flush_cache' ) );
add_action( 'cploc_location_meta_details', array( $locations, 'location_meta' ), 10, 2 );
add_action( 'admin_init', array( $locations, 'maybe_force_pull' ) );

$youtube_box = settings_box();
$youtube->set_context();
$youtube->settings( $youtube_box );
$youtube->set_context( 'loc' );
$youtube->settings( settings_box() );
$youtube->set_context();
expect( count( $youtube_box->fields ) > 0, 'youtube settings box still receives fields' );

$resi_box = settings_box();
$resi->set_context();
$resi->settings( $resi_box );
$resi->set_context( 'loc' );
$resi->settings( settings_box() );
$resi->set_context();
expect( count( $resi_box->fields ) > 0, 'resi settings box still receives fields' );

$bare_box = settings_box();
$bare->set_context();
$bare->settings( $bare_box );
expect( count( $bare_box->fields ) > 0, 'service settings box still receives fields' );

$advanced_box = settings_box();
$locations->advanced_settings( $advanced_box );
expect( count( $advanced_box->fields ) > 0, 'advanced settings box still receives fields' );

$live_field = new class {
	public $value = '1';
	public $data_to_save = array();

	public function get_cmb() {
		return $this;
	}
};
reset_state();
$youtube->set_context();
$youtube->live_override( true, 'updated', $live_field );
expect(
	! empty( $GLOBALS['state_changes'] ) && ! empty( $live_field->data_to_save['live_start'] ),
	'manual live field still updates'
);
$youtube->set_context();

$youtube->set_context( 'keep' );
$resi->set_context( 'keep' );
$bare->set_context( 'keep' );
expect_quiet_dispatch( 'cp_live_settings', 'settings hook with a request array returns quietly' );
expect_quiet_dispatch( 'cp_live_settings_advanced', 'advanced settings hook with a request array returns quietly' );
expect_quiet_dispatch( 'cmb2_save_field_is_live', 'field save hook with a request array returns quietly' );
expect_quiet_dispatch( 'cmb2_save_field_youtube_is_live', 'youtube field save hook with a request array returns quietly' );
expect_quiet_dispatch( 'cmb2_save_field_resi_is_live', 'resi field save hook with a request array returns quietly' );
expect(
	'keep' === $youtube->context && 'keep' === $resi->context && 'keep' === $bare->context,
	'field save hook leaves service context unchanged'
);
$youtube->set_context();
$resi->set_context();
$bare->set_context();

expect_quiet_dispatch( 'cploc_location_meta_details', 'location meta hook with a request array returns quietly' );
expect_quiet_dispatch( 'save_post_cploc_location', 'location save hook with a request array returns quietly' );
expect_quiet_dispatch( 'cmb2_admin_init', 'settings registration hook with a request array returns quietly' );
expect( empty( $GLOBALS['boxes_registered'] ), 'settings registration hook with a request array does not register pages' );

$active_before = $services->active;
expect_quiet_dispatch( 'plugins_loaded', 'service registration hook with a request array returns quietly' );
expect( $active_before === $services->active, 'service registration hook does not replace services' );
expect( false === $integrations->cp_locations, 'integration registration hook does not load integrations' );
expect( empty( $GLOBALS['plugin_active_called'] ), 'setup hook with a request array returns quietly' );

expect_quiet_dispatch( 'admin_init', 'admin init hook with a request array returns quietly' );
reset_state();
$GLOBALS['service_updated'] = false;
$GLOBALS['test_options']    = array(
	'cp_live_advanced_options' => array( 'feed_check' => 1 ),
);
$GLOBALS['pagenow'] = 'post.php';
$_GET               = array(
	'cp_action' => 'admin_init',
	'post'      => '10',
);
$_POST              = array();
$_REQUEST           = $_GET;
$services->active   = array(
	new class {
		public function check() {
			$GLOBALS['service_checked'] = true;
		}

		public function update( $key, $value ) {
			$GLOBALS['service_updated'] = true;
		}
	},
);
$quiet_force = true;
try {
	$GLOBALS['test_core']->request_actions();
} catch ( Throwable $e ) {
	$quiet_force = false;
}
expect(
	$quiet_force && empty( $GLOBALS['service_updated'] ) && empty( $GLOBALS['state_changes'] ),
	'force pull with a request array returns quietly'
);

expect_quiet_dispatch( 'init', 'init hook with a request array returns quietly' );
expect( empty( $GLOBALS['schedule_checked'] ) && empty( $GLOBALS['locale_loaded'] ), 'init hook does not register a schedule or load translations' );
expect_quiet_dispatch( 'wp_enqueue_scripts', 'script registration hook with a request array returns quietly' );

reset_state();
$missing_errors = 0;
foreach ( array( $youtube, $resi, $bare ) as $service ) {
	$service->set_context( 'keep' );

	try {
		$service->settings();
		$service->live_override();
	} catch ( Throwable $e ) {
		$missing_errors++;
	}

	if ( 'keep' !== $service->context ) {
		$missing_errors++;
	}

	$service->set_context();
}

try {
	$locations->advanced_settings();
	$locations->location_meta();
	$locations->flush_cache();
} catch ( Throwable $e ) {
	$missing_errors++;
}

expect( 0 === $missing_errors, 'settings callbacks with no arguments return quietly' );

reset_state();
$GLOBALS['service_updated'] = false;
$GLOBALS['test_options']    = array(
	'cp_live_advanced_options' => array( 'feed_check' => 1 ),
);
$services->active = array(
	new class {
		public function check() {}

		public function update( $key, $value ) {
			$GLOBALS['service_updated'] = true;
		}
	},
);
do_action( 'admin_init' );
expect( ! empty( $GLOBALS['service_updated'] ), 'force pull still runs from admin init' );

reset_state();
do_action( 'init' );
expect(
	! empty( $GLOBALS['schedule_checked'] ) && ! empty( $GLOBALS['locale_loaded'] ) && empty( $GLOBALS['state_changes'] ),
	'init still checks the schedule and loads translations'
);

$dist_root = dirname( __DIR__ ) . '/dist';
$manifest  = json_encode(
	array(
		'wpackioEp' => array(
			'main' => array(
				'assets' => array(
					'js'  => array( 'main.js' ),
					'css' => array( 'main.css' ),
				),
			),
		),
	)
);
foreach ( array( 'styles', 'scripts' ) as $entry ) {
	$entry_dir = $dist_root . '/' . $entry;
	if ( ! is_dir( $entry_dir ) ) {
		mkdir( $entry_dir, 0777, true );
	}
	file_put_contents( $entry_dir . '/manifest.json', $manifest );
}
reset_state();
try {
	do_action( 'wp_enqueue_scripts' );
	$scripts_ran = ! empty( $GLOBALS['scripts_enqueued'] ) && ! empty( $GLOBALS['styles_enqueued'] );
} catch ( Throwable $e ) {
	echo 'error script registration: ' . $e->getMessage() . "\n";
	$scripts_ran = false;
}
foreach ( array( 'styles', 'scripts' ) as $entry ) {
	$manifest_file = $dist_root . '/' . $entry . '/manifest.json';
	if ( is_file( $manifest_file ) ) {
		unlink( $manifest_file );
	}
}
expect( $scripts_ran, 'script registration still runs from the scripts hook' );

require_once dirname( __DIR__ ) . '/includes/ChurchPlugins/Setup/Admin/License.php';
reset_state();
do_action( 'cmb2_admin_init' );
expect(
	! empty( $GLOBALS['boxes_registered'] ) && in_array( 'cp_live_main_options_page', $GLOBALS['registered_box_ids'], true ),
	'settings registration still runs from the settings hook'
);

set_include_path( dirname( __DIR__ ) . '/includes' . PATH_SEPARATOR . get_include_path() );
reset_state();
do_action( 'plugins_loaded' );
expect( isset( $services->active['youtube'] ), 'service registration still loads services' );
expect( $integrations->cp_locations instanceof CP_Locations, 'integration registration still loads' );
expect( isset( $plugin->setup ), 'setup still runs from the plugins loaded hook' );

reset_state();
do_action( 'save_post_cploc_location', 10 );
expect( 1 === $GLOBALS['state_changes'], 'location save with a post id still clears the cache' );

if ( $failures > 0 ) {
	fwrite( STDERR, "{$checks} checks, {$failures} failed\n" );
	exit( 1 );
}

echo "{$checks} checks passed\n";
exit( 0 );
