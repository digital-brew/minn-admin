<?php
/**
 * Bundled adapter: Independent Analytics (traffic provider).
 *
 * Pageviews come from {prefix}independent_analytics_views (one row per view,
 * `viewed_at` datetime); visitors from {prefix}independent_analytics_sessions
 * (COUNT(DISTINCT visitor_id) per day of `created_at`).
 *
 * @package minn-admin
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'minn_admin_traffic', function ( $traffic, $days ) {
	if ( null !== $traffic || ! defined( 'IAWP_VERSION' ) ) {
		return $traffic;
	}

	global $wpdb;
	$views    = $wpdb->prefix . 'independent_analytics_views';
	$sessions = $wpdb->prefix . 'independent_analytics_sessions';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $views ) ) !== $views ) {
		return $traffic;
	}

	$days       = max( 1, (int) $days );
	$cur_start  = gmdate( 'Y-m-d', time() - ( $days - 1 ) * DAY_IN_SECONDS );
	$prev_start = gmdate( 'Y-m-d 00:00:00', time() - ( 2 * $days - 1 ) * DAY_IN_SECONDS );

	$view_rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT DATE(viewed_at) AS d, COUNT(*) AS p FROM {$views} WHERE viewed_at >= %s GROUP BY d", // phpcs:ignore
		$prev_start
	) );
	$visitor_rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT DATE(created_at) AS d, COUNT(DISTINCT visitor_id) AS v FROM {$sessions} WHERE created_at >= %s GROUP BY d", // phpcs:ignore
		$prev_start
	) );
	if ( ! $view_rows && ! $visitor_rows ) {
		return $traffic;
	}

	$map  = array();
	$prev = 0;
	foreach ( (array) $visitor_rows as $row ) {
		if ( $row->d >= $cur_start ) {
			$map[ $row->d ] = array( 'visitors' => (int) $row->v, 'pageviews' => 0 );
		} else {
			$prev += (int) $row->v;
		}
	}
	foreach ( (array) $view_rows as $row ) {
		if ( $row->d < $cur_start ) {
			continue;
		}
		if ( ! isset( $map[ $row->d ] ) ) {
			$map[ $row->d ] = array( 'visitors' => 0, 'pageviews' => 0 );
		}
		$map[ $row->d ]['pageviews'] = (int) $row->p;
	}

	return array(
		'source'        => 'Independent Analytics',
		'days'          => $map,
		'prev_visitors' => $prev,
	);
}, 10, 2 );
