<?php
/**
 * Bundled adapter: Burst Statistics (traffic provider).
 *
 * Burst stores one row per pageview in {prefix}burst_statistics with a unix
 * `time` and a per-visitor `uid`, so daily totals are COUNT(*) for pageviews
 * and COUNT(DISTINCT uid) for visitors.
 *
 * @package minn-admin
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'minn_admin_traffic', function ( $traffic, $days ) {
	if ( null !== $traffic || ! defined( 'BURST_VERSION' ) ) {
		return $traffic;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'burst_statistics';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return $traffic;
	}

	$days       = max( 1, (int) $days );
	$cur_start  = gmdate( 'Y-m-d', time() - ( $days - 1 ) * DAY_IN_SECONDS );
	$prev_since = time() - ( 2 * $days - 1 ) * DAY_IN_SECONDS;

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT DATE(FROM_UNIXTIME(time)) AS d, COUNT(DISTINCT uid) AS v, COUNT(*) AS p FROM {$table} WHERE time >= %d GROUP BY d", // phpcs:ignore
		$prev_since
	) );
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
		'source'        => 'Burst Statistics',
		'days'          => $map,
		'prev_visitors' => $prev,
	);
}, 10, 2 );
