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


		// if we have multiple broadcasts detected, try to find the one that is schedule to start when we expect it to
		if ( count( $body->items ) > 1 ) {
			$ids = [];

			foreach( $body->items as $item ) {
				$ids[] = $item->id->videoId;
			}

			$videos = $this->get_video_details( $ids );

			if ( ! empty( $videos->items ) ) {
				$timestamp = 999999999999999999999;

				// loop through the broadcasts and use the one that is happening next
				foreach( $videos->items as $v ) {
					if ( empty( $v->liveStreamingDetails ) || empty( $v->liveStreamingDetails->scheduledStartTime ) ) {
						continue;
					}

					if ( ! $start_time = strtotime( $v->liveStreamingDetails->scheduledStartTime ) ) {
						continue;
					}

					// if the scheduled time is more than the current time + buffer x2, move on
					if ( $start_time > $timestamp ) {
						continue;
					}

					$video = $v->id;
					$timestamp = $start_time;
				}
			}

		}

		$url = sprintf( 'https://youtube.com/watch?v=%s', urlencode( $video ) );

		$this->update( 'video_url', $url );

		$this->set_live();
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
		if ( ! $video_url = $this->get( 'video_url' ) ) {
			return '';
		}

		// get ID from URL. Check for youtube.com or youtu.be formats
		preg_match( '/v=([^&]+)|youtu\.be\/([^&]+)/', $video_url, $matches );

		if ( $matches[1] ) {
			$video_id = $matches[1];
		} else if ( $matches[2] ) {
			$video_id = $matches[2];
		} else {
			$video_id = '';
		}

		if ( empty( $video_id ) ) {
			return '';
		}

		$output = sprintf( '<div class="cp-live-embed-container" style="width: 750px;">' );

		$output .= sprintf( '<iframe class="cp-live-embed" style="max-width:100%%;" width="750" height="500" src="https://www.youtube.com/embed/%s" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>', $video_id );
  
		if ( $this->get( 'show_subscribe_button', 'show' ) == 'show' ) {
			$channel_id = $this->get( 'channel_id' );

			if ( $channel_id && ! wp_script_is( 'google_platform_js', 'enqueued' ) ) {
				wp_enqueue_script( 'google_platform_js', 'https://apis.google.com/js/platform.js' );
				$output .= "<div class='cp-subscribe-btn'><div class='g-ytsubscribe' data-channelid='$channel_id' data-layout='default' data-count='default'></div></div>";
			}
		}

		$output .= '</div>';

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