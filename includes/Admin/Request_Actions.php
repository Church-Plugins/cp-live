<?php

namespace CP_Live\Admin;

/**
 * Adds capability and nonce checks to admin request actions.
 */
class Request_Actions {

	/**
	 * Capability used by the CP Live settings screens.
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Hook fired for a live-status check.
	 */
	const LIVE_CHECK = 'cp_live_check';

	/**
	 * Whether this call may run.
	 *
	 * Scheduled cron invokes the hook directly and does not send request
	 * arguments, so that path keeps running. An admin request action must be
	 * sent by a user who has the settings capability and must include a valid
	 * nonce for the action (see wp_nonce_url()).
	 *
	 * @param string     $action  Hook name for the request action.
	 * @param mixed|null $request Request vars passed into the hook, when present.
	 *
	 * @return bool
	 */
	public static function is_allowed( $action, $request = null ) {
		if ( ! self::is_admin_request( $action, $request ) ) {
			return true;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return false;
		}

		return self::has_valid_nonce( $action, $request );
	}

	/**
	 * Whether this invocation came from an admin request action.
	 *
	 * The core request handler passes the query or post vars as the hook
	 * argument. A direct hook call is treated the same way when that action
	 * is present on the request, except while cron is running the schedule.
	 *
	 * @param string     $action
	 * @param mixed|null $request
	 *
	 * @return bool
	 */
	protected static function is_admin_request( $action, $request ) {
		if ( self::matches_action( $action, $request ) ) {
			return true;
		}

		if ( self::doing_cron() ) {
			return false;
		}

		return self::matches_action( $action, $_REQUEST );
	}

	/**
	 * Whether the given vars are for this request action.
	 *
	 * @param string     $action
	 * @param mixed|null $request
	 *
	 * @return bool
	 */
	protected static function matches_action( $action, $request ) {
		if ( ! is_array( $request ) || ! isset( $request['cp_action'] ) ) {
			return false;
		}

		return self::sanitize_action( $request['cp_action'] ) === $action;
	}

	/**
	 * Normalize a cp_action value.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected static function sanitize_action( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		return sanitize_key( wp_unslash( (string) $value ) );
	}

	/**
	 * Whether WordPress is running a scheduled cron event.
	 *
	 * @return bool
	 */
	protected static function doing_cron() {
		if ( function_exists( 'wp_doing_cron' ) ) {
			return (bool) wp_doing_cron();
		}

		return defined( 'DOING_CRON' ) && DOING_CRON;
	}

	/**
	 * Whether the request includes a valid nonce for the action.
	 *
	 * @param string     $action
	 * @param mixed|null $request
	 *
	 * @return bool
	 */
	protected static function has_valid_nonce( $action, $request ) {
		$nonce = self::nonce_from( $request );

		if ( '' === $nonce ) {
			$nonce = self::nonce_from( $_REQUEST );
		}

		if ( '' === $nonce ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Read a nonce string from request vars.
	 *
	 * @param mixed|null $request
	 *
	 * @return string
	 */
	protected static function nonce_from( $request ) {
		if ( ! is_array( $request ) || ! isset( $request['_wpnonce'] ) || is_array( $request['_wpnonce'] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( (string) $request['_wpnonce'] ) );
	}

}
