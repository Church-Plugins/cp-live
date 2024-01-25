<?php
/**
 * Legacy shortcode for Locations
 */

$output = '<div class="cp-live-container">';

$found = false;
$location_id = apply_filters( 'cp_live_video_location_id_default', get_query_var( 'cp_location_id' ) );

$locations = \CP_Live\Integrations\CP_Locations::get_instance();

if ( $location_id ) {
	$embed = $locations::get_location_embed( $location_id );
	
	if ( empty( $embed ) ) {
		_e( 'No live feeds were found', 'cp-theme-default' );
		return;
	} else {
		$output .= $embed;
	}
} else {
	foreach ( $locations->sites_to_check() as $location_id => $data ) {
		$embed = $locations::get_location_embed( $location_id );
		
		if ( empty( $embed ) ) {
			continue;
		}
		
		$found = true;

		$output .= sprintf( '<hr /><div class="cp-live-location ast-row"><div class="cp-live-location--video ast-grid-common-col ast-width-md-6">%s</div><div class="cp-live-location--info ast-width-md-6 ast-grid-common-col">%s</div></div>',
			$embed,
			sprintf( '<h3><a href="%s">%s</a></h3><p>%s</p>', get_permalink( $location_id ) . '/live/', get_the_title( $location_id ), do_shortcode( "[cp-location-data field=live_time_description location=$location_id]" ) ),
		);
	}

	if ( empty( $found ) ) {
		_e( 'No live feeds were found', 'cp-theme-default' );
		return;
	}

}

$output .= '</div>';

echo $output;