<?php
/**
 * Bundled adapter: WP Statistics (traffic provider).
 *
 * Prefers the {prefix}statistics_summary_totals table (date, visitors, views —
 * populated by WP Statistics 14.10+ daily aggregation). When that has no rows
 * for the window, falls back to aggregating the raw visitor table
 * (one row per visitor per day; `hits` = that visitor's views).
 *
 * @package minn-admin
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'minn_admin_traffic', function ( $traffic, $days ) {
	if ( null !== $traffic || ! defined( 'WP_STATISTICS_VERSION' ) ) {
		return $traffic;
	}

	global $wpdb;
	$days       = max( 1, (int) $days );
	$cur_start  = gmdate( 'Y-m-d', time() - ( $days - 1 ) * DAY_IN_SECONDS );
	$prev_start = gmdate( 'Y-m-d', time() - ( 2 * $days - 1 ) * DAY_IN_SECONDS );

	$rows    = array();
	$summary = $wpdb->prefix . 'statistics_summary_totals';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $summary ) ) === $summary ) {
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT date AS d, visitors AS v, views AS p FROM {$summary} WHERE date >= %s", // phpcs:ignore
			$prev_start
		) );
	}
	if ( ! $rows ) {
		$visitor = $wpdb->prefix . 'statistics_visitor';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $visitor ) ) !== $visitor ) {
			return $traffic;
		}
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT last_counter AS d, COUNT(*) AS v, COALESCE(SUM(hits), 0) AS p FROM {$visitor} WHERE last_counter >= %s GROUP BY last_counter", // phpcs:ignore
			$prev_start
		) );
	}
	if ( ! $rows ) {
		return $traffic;
	}

	$map  = array();
	$prev = 0;
	foreach ( $rows as $row ) {
		if ( $row->d >= $cur_start ) {
			$map[ $row->d ] = array(
				'visitors'  => (int) $row->v,
				'pageviews' => (int) $row->p,
			);
		} else {
			$prev += (int) $row->v;
		}
	}

	return array(
		'source'        => 'WP Statistics',
		'days'          => $map,
		'prev_visitors' => $prev,
	);
}, 10, 2 );
