#!/usr/bin/php
<?php
/**
 * Look for the latest release of vip-go-ci
 * on GitHub, and output the release-number
 * to STDOUT. In the process, do sanity-checking.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

$github_url         = 'https://api.github.com/repos/automattic/vip-go-ci/releases';
$client_id          = 'automattic-vip-go-ci-release-checker';
$github_api_version = 'X-GitHub-Api-Version: 2022-11-28'; // If updated, update defines.php too.

$ch = curl_init();

curl_setopt( $ch, CURLOPT_URL, $github_url );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 20 );
curl_setopt( $ch, CURLOPT_USERAGENT, $client_id );
curl_setopt( $ch, CURLOPT_MAXREDIRS, 0 );
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
curl_setopt( $ch, CURLOPT_HTTPHEADER, array( $github_api_version ) );

$resp_data_raw = curl_exec( $ch );


/*
 * If cURL fails, abort.
 */

if ( false === $resp_data_raw ) {
	exit( 255 );
}


$resp_data = json_decode(
	$resp_data_raw
);

unset( $resp_data_raw );


/*
 * If JSON-decode fails, abort
 */
if ( null === $resp_data ) {
	exit( 255 );
}


/*
 * Sanity-check: Is version defined and
 * is it a number?
 */

if (
	( ! isset( $resp_data[0]->tag_name ) ) ||
	( ! preg_match( '/^(\d+\.)?(\d+\.)?(\*|\d+)$/', $resp_data[0]->tag_name ) )
) {
	exit( 255 );
}


echo $resp_data[0]->tag_name;

