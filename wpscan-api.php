<?php
/**
 * WPScan API related functions for vip-go-ci.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Call WPScan API for the plugin or theme slug specified. Return the results.
 *
 * @param string $wpscan_slug         Plugin or theme slug.
 * @param string $wpscan_type         Type of scan, plugin or theme
 *                                    -- use VIPGOCI_WPSCAN_PLUGIN or VIPGOCI_WPSCAN_THEME defines.
 * @param string $wpscan_api_base_url Base WPScan API URL.
 * @param string $wpscan_access_token WPScan access token to use.
 *
 * @return null|array Results from WPScan API as array on success, null on failure.
 */
function vipgoci_wpscan_do_scan_via_api(
	string $wpscan_slug,
	string $wpscan_type,
	string $wpscan_api_base_url,
	string $wpscan_access_token,
) :null|array {
	/*
	 * Construct WPScan API URL
	 */
	$wpscan_complete_url =
		$wpscan_api_base_url;

	if ( VIPGOCI_WPSCAN_PLUGIN === $wpscan_type ) {
		$wpscan_complete_url .= '/plugins';
	} elseif ( VIPGOCI_WPSCAN_THEME === $wpscan_type ) {
		$wpscan_complete_url .= '/themes';
	} else {
		vipgoci_sysexit(
			'Incorrect usage of $wpscan_type parameter in ' . __FUNCTION__,
			array(),
			VIPGOCI_EXIT_INTERNAL_ERROR
		);
	}

	$wpscan_complete_url .= '/' . rawurlencode( $wpscan_slug );

	/*
	 * Call WPScan API.
	 */
	vipgoci_log(
		'Calling WPScan API for slug',
		array(
			'wpscan_slug'         => $wpscan_slug,
			'wpscan_type'         => $wpscan_type,
			'wpscan_complete_url' => $wpscan_complete_url,
		),
		0
	);

	$wpscan_report_json = vipgoci_http_api_fetch_url(
		$wpscan_complete_url,
		array(
			'wpscan_token' => $wpscan_access_token,
		),
	);

	vipgoci_log(
		'WScan API returned data',
		array(
			'wpscan_slug'                => $wpscan_slug,
			'wpscan_type'                => $wpscan_type,
			'wpscan_complete_url'        => $wpscan_complete_url,
			'wpscan_report_json_preview' => vipgoci_preview_string( $wpscan_report_json ),
		),
		0
	);

	// @todo: Audit if any expected fields are missing.

	if ( null === $wpscan_report_json ) {
		return null;
	} else {
		return json_decode(
			$wpscan_report_json,
			true
		);
	}
}

/**
 * Filter away any security problems fixed in earlier versions
 * of the theme/plugin as indicated in WPScan API results.
 *
 * @param string $wpscan_slug    Plugin or theme slug.
 * @param string $version_number Version number of the plugin to use as baseline.
 * @param array  $wpscan_results WPScan API results array.
 *
 * @return null|array WPScan results on success, with only vulnerabilities affecting the current or later versions listed. On failure, null.
 */
function vipgoci_wpscan_filter_fixed_vulnerabilities(
	string $wpscan_slug,
	string $version_number,
	array $wpscan_results
) :null|array {
	if ( ! isset( $wpscan_results[ $wpscan_slug ]['vulnerabilities'] ) ) {
		return null;
	}

	// Filter away vulnerabilities that affect versions older than $version_number.
	$wpscan_results[ $wpscan_slug ] ['vulnerabilities'] = array_filter(
		$wpscan_results[ $wpscan_slug ]['vulnerabilities'],
		function( $vuln_item ) use ( $version_number ) {
			if ( ! isset( $vuln_item['fixed_in'] ) ) {
				return false;
			}

			return version_compare(
				$version_number,
				$vuln_item['fixed_in'],
				'<='
			);
		}
	);

	// Ensure keys are reset before returning new value.
	$wpscan_results[ $wpscan_slug ]['vulnerabilities'] = array_values(
		$wpscan_results[ $wpscan_slug ]['vulnerabilities']
	);

	return $wpscan_results;
}

