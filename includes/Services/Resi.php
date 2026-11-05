<?php

namespace CP_Live\Services;

use ChurchPlugins\Models\Log;

class Resi extends Service{

	public $id = 'resi';

	public function add_actions() {
		parent::add_actions();
	}

	/**
	 * Check the sites to see if any of them are live
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function check() {
		$stream = $this->get( 'stream_url', false );

		$xml = '';

		if ( ! empty( $stream ) ) {
			$xml = simplexml_load_file( $stream );

			if ( ! empty( $xml ) ) {
				$status = (string) strtolower( $xml->attributes()->{'type'} );
				$xml = $xml->asXML();
			} else {
				$status = 'stream_empty';
			}
		} else {
			$status = 'stream_missing';
		}

		Log::insert( [
			'object_type' => 'service-resi',
			'action'      => 'check',
			'data'        => serialize( [ 'status' => $status, 'xml' => $xml ] ),
		] );

		$this->update( 'status', $status );

		if ( 'dynamic' === $status ) {
			$this->set_live();
		}
	}

	/**
	 * Return the embed for Resi
	 *
	 * @return string
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_embed() {
		if ( ! $embed_id = $this->get( 'embed_id' ) ) {
			return '';
		}

		ob_start(); ?>
        <div id="resi-video-player" data-embed-id="<?php echo $embed_id ?>"></div>
        <script type="application/javascript" src="https://control.resi.io/webplayer/loader.min.js"></script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Resi Settings
	 *
	 * @param $cmb
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function settings( $cmb = null ) {
		if ( ! self::is_cmb2_box( $cmb ) ) {
			return;
		}

		// add prefix to fields if we are not in the global context. Other services may use the same id.
		$prefix = 'global' != $this->context ? $this->id . '_' : '';

		$cmb->add_field( [
			'name'            => __( 'Embed ID', 'cp-live' ),
			'desc'            => __( 'Add the Embed ID found in the Resi Web Channel embed code.', 'cp-live' ),
			'id'              => $prefix . 'embed_id',
			'type'            => 'text',
			'sanitization_cb' => 'sanitize_key',
			'escape_cb'       => 'sanitize_key',
		] );

		$cmb->add_field( [
			'name' => __( 'Stream URL', 'cp-live' ),
			'desc' => __( 'Add the Stream URL from Resi Web Channel Profile that ends in Manifest.mpd', 'cp-live' ),
			'id'   => $prefix . 'stream_url',
			'type' => 'text_url'
		] );

		$cmb->add_field( [
			'name' => __( 'Stream Status', 'cp-live' ),
			'desc' => __( 'The status of the stream last time it was checked.', 'cp-live' ),
			'id'   => $prefix . 'status',
			'type' => 'text',
			'attributes' => [
				'disabled' => true,
			],
		] );

		parent::settings( $cmb );
	}

}