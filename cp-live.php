<?php
/**
 * Plugin Name: CP Live
 * Plugin URL: https://churchplugins.com
 * Description: Automatically detect and show live video feeds.
 * Version: 1.1.0
 * Author: Church Plugins
 * Author URI: https://churchplugins.com
 * Text Domain: cp-live
 * Domain Path: languages
 */

if( !defined( 'CP_LIVE_PLUGIN_VERSION' ) ) {
	 define ( 'CP_LIVE_PLUGIN_VERSION',
	 	'1.1.0'
	);
}

require_once( dirname( __FILE__ ) . "/includes/Constants.php" );

require_once( CP_LIVE_PLUGIN_DIR . "/includes/ChurchPlugins/init.php" );
require_once( CP_LIVE_PLUGIN_DIR . 'vendor/autoload.php' );


use CP_Live\_Init as Init;

/**
 * @var CP_Live\_Init
 */
global $cp_live;
$cp_live = cp_live();

/**
 * @return CP_Live\_Init
 */
function cp_live() {
	return Init::get_instance();
}

/**
 * Load plugin text domain for translations.
 *
 * @return void
 */
function cp_live_load_textdomain() {

	// Traditional WordPress plugin locale filter
	$get_locale = get_user_locale();

	/**
	 * Defines the plugin language locale used in RCP.
	 *
	 * @var string $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
	 *                  otherwise uses `get_locale()`.
	 */
	$locale        = apply_filters( 'plugin_locale',  $get_locale, 'cp-live' );
	$mofile        = sprintf( '%1$s-%2$s.mo', 'cp-live', $locale );

	// Setup paths to current locale file
	$mofile_global = WP_LANG_DIR . '/cp-live/' . $mofile;

	if ( file_exists( $mofile_global ) ) {
		// Look in global /wp-content/languages/cp-live folder
		load_textdomain( 'cp-live', $mofile_global );
	}

}
add_action( 'init', 'cp_live_load_textdomain' );
