<?php

namespace CP_Live\Services;

use ChurchPlugins\Models\Log;
use CP_Live\Admin\Settings;

class YouTube extends Service{

	public $id = 'youtube';

	public function add_actions() {
		parent::add_actions();
	}

	/**
	 * Check the status of a channel, update the meta if live
	 *
	 * @return void
	 * @since  1.0.1
	 *
	 * @author Tanner Moushey
	 */
	public function check() {

		// if we are live and already have a video url, break early
		if ( $this->is_live() && $this->get('video_url') ) {
			return;
		}

		$channel_id = $this->get( 'channel_id' );
		$api_key    = $this->get( 'api_key' );
		$type       = $this->get( 'video_type', 'live' );

		if ( empty( $channel_id ) || empty( $api_key ) ) {
			return;
		}

		$args = [
			'part'      => 'snippet',
			'type'      => 'video',
			'eventType' => $type,
			'channelId' => $channel_id,
			'key'       => $api_key,
		];

		$url = 'https://www.googleapis.com/youtube/v3/search';

		$search   = add_query_arg( $args, $url );
		$response = wp_remote_get( $search );

		Log::insert( [
			'object_type' => 'service-youtube',
			'action'      => 'check',
			'data'        => serialize( $response ),
		] );

		// if we don't have a valid body, bail early
		if ( ! $body = wp_remote_retrieve_body( $response ) ) {
			return;
		}

		$body = json_decode( $body );

		// make sure we have items
		if ( empty( $body->items ) ) {
			return;
		}

		// default to first video in feed
		$video = $body->items[0]->id->videoId;

		$ids = [];

		foreach( $body->items as $item ) {
			$ids[] = $item->id->videoId;
		}

		// Look up schedule details so we can choose the right broadcast. This runs even for a
		// single result so an abandoned broadcast (scheduled in the past, never started or ended)
		// is not embedded on its own.
		$videos = $this->get_video_details( $ids );

		if ( ! empty( $videos->items ) ) {
			$selected = $this->select_broadcast( $videos->items, $type );

			// every broadcast returned is stale, nothing to show
			if ( ! $selected ) {
				return;
			}

			$video = $selected;
		}

		$url = sprintf( 'https://youtube.com/watch?v=%s', urlencode( $video ) );

		$this->update( 'video_url', $url );

		$this->set_live();
	}

	/**
	 * Choose the broadcast to embed from the video details returned by the API.
	 *
	 * Broadcasts that are actually live always win. Otherwise, broadcasts scheduled to start more
	 * than a couple of hours ago are ignored as stale (e.g. an "upcoming" broadcast that was
	 * scheduled but never streamed), and the remaining broadcast scheduled closest to now is used.
	 *
	 * Closest to now (rather than next in the future) is deliberate: checks run around service
	 * time, so the broadcast we want is usually scheduled a few minutes before the check.
	 *
	 * @param array  $videos Video resources from the videos endpoint (liveStreamingDetails part).
	 * @param string $type   The eventType used for the search (live, upcoming, completed).
	 *
	 * @return string|false The selected video id, or false if every broadcast is stale.
	 * @since  1.1.2
	 */
	protected function select_broadcast( $videos, $type = 'live' ) {
		$now = time();

		/**
		 * How long after its scheduled start an unstarted broadcast is still considered valid.
		 *
		 * @param int $stale_after Seconds. Default 2 hours.
		 */
		$stale_after = absint( apply_filters( 'cp_live_youtube_stale_broadcast_after', 2 * HOUR_IN_SECONDS ) );

		$video    = false;
		$distance = PHP_INT_MAX;

		foreach( $videos as $v ) {
			$details = $v->liveStreamingDetails ?? null;

			// a broadcast that has started and not ended is the one we want
			if ( ! empty( $details->actualStartTime ) && empty( $details->actualEndTime ) ) {
				return $v->id;
			}

			// results from a live search are live even if the details lag behind (e.g. no start time yet)
			if ( 'live' === $type && empty( $details->actualEndTime ) ) {
				return $v->id;
			}

			if ( empty( $details->scheduledStartTime ) ) {
				continue;
			}

			if ( ! $start_time = strtotime( $details->scheduledStartTime ) ) {
				continue;
			}

			// scheduled too long ago to still be the broadcast we are waiting on
			if ( $start_time < $now - $stale_after ) {
				continue;
			}

			$diff = abs( $start_time - $now );

			if ( $diff < $distance ) {
				$video    = $v->id;
				$distance = $diff;
			}
		}

		return $video;
	}

	/**
	 * Get streaming details for the provided broadcast ids
	 *
	 * @param $ids
	 *
	 * @return false|mixed
	 * @throws \ChurchPlugins\Exception
	 * @since  1.0.4
	 *
	 * @author Tanner Moushey
	 */
	protected function get_video_details( $ids ) {
		if ( empty( $ids ) ) {
			return false;
		}

		$args = [
			'part' => 'liveStreamingDetails',
			'type' => 'video',
			'id'   => implode( ',', $ids ),
			'key'  => $this->get( 'api_key' ),
		];

		$url = 'https://www.googleapis.com/youtube/v3/videos';

		$search   = add_query_arg( $args, $url );
		$response = wp_remote_get( $search );

		Log::insert( [
			'object_type' => 'service-youtube',
			'action'      => 'video-details',
			'data'        => serialize( $response ),
		] );

		// if we don't have a valid body, bail early
		if ( ! $body = wp_remote_retrieve_body( $response ) ) {
			return false;
		}

		return json_decode( $body );
	}

	/**
	 * Return the embed for YouTube
	 *
	 * @return string
	 * @since  1.0.0
	 * @updated 1.1.0 add subscribe button
	 *
	 * @author Tanner Moushey
	 */
	public function get_embed() {
		global $wp_embed;

		if ( ! $video_url = $this->get( 'video_url' ) ) {
			return '';
		}

		$output = $wp_embed->autoembed( $video_url );

		if ( $this->get( 'show_subscribe_button', 'show' ) == 'show' ) {
			$channel_id = $this->get( 'channel_id' );

			if ( $channel_id && ! wp_script_is( 'google_platform_js', 'enqueued' ) ) {
				wp_enqueue_script( 'google_platform_js', 'https://apis.google.com/js/platform.js' );
				$output .= "<div class='cp-subscribe-btn'><div class='g-ytsubscribe' data-channelid='$channel_id' data-layout='default' data-count='default'></div></div>";
			}

		}

		return $output;
  }
	/**
	 * YouTube Settings
	 *
	 * @param $cmb
	 *
	 * @since  1.0.0
	 * @updated 1.1.0 add subscribe button settings
	 *
	 * @author Tanner Moushey
	 */
	public function settings( $cmb ) {
		// add prefix to fields if we are not in the global context. Other services may use the same id.
		$prefix = 'global' != $this->context ? $this->id . '_' : '';

		$cmb->add_field( [
			'name'        => __( 'YouTube Channel ID', 'cp-live' ),
			'id'          => $prefix . 'channel_id',
			'type'        => 'text',
			'description' => __( 'The ID of the channel to check.', 'cp-live' ),
		] );

		$cmb->add_field( [
			'name'        => __( 'YouTube API Key', 'cp-live' ),
			'id'          => $prefix . 'api_key',
			'type'        => 'text',
			'description' => __( 'Used to connect to the YouTube API.', 'cp-live' ),
		] );

		$cmb->add_field( [
			'name'        => __( 'YouTube API Key', 'cp-live' ),
			'id'          => $prefix . 'api_key',
			'type'        => 'text',
			'description' => __( 'Used to connect to the YouTube API.', 'cp-live' ),
		] );

		$cmb->add_field( [
			'name'        => __( 'Video Type', 'cp-live' ),
			'id'          => $prefix . 'video_type',
			'type'        => 'radio_inline',
			'description' => __( 'Whether to pull a currently active broadcast (Live) or the next scheduled broadcast (Upcoming).', 'cp-live' ),
			'default'     => 'live',
			'options'     => [
				'live'     => __( 'Live', 'cp-live' ),
				'upcoming' => __( 'Upcoming', 'cp-live' ),
			],
		] );

		$cmb->add_field( [
			'name'        => __( 'Video URL', 'cp-live' ),
			'id'          => $prefix . 'video_url',
			'type'        => 'text_url',
			'description' => __( 'The URL of the most recent or currently live video.', 'cp-live' ),
		] );

    $cmb->add_field( [
			'name'        => __( 'Show Subscribe Button', 'cp-live' ),
			'id'          => $prefix . 'show_subscribe_button',
			'type'        => 'radio_inline',
			'description' => __( 'Display a button for viewers to subscribe to your channel', 'cp-live' ),
			'default'     => 'show',
			'options'     => [
				'show' => __( 'Show', 'cp-live' ),
				'hide' => __( 'Hide', 'cp-live' ),
			],
		] );

		parent::settings( $cmb );
	}

}