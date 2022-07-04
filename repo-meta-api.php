<?php
/**
 * Support-level label functionality.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Fetch meta-data for repository from
 * repo-meta API, cache the results in memory.
 *
 * @param string     $repo_meta_api_base_url     URL to repo-meta API.
 * @param int|string $repo_meta_api_user_id      User ID for repo-meta API.
 * @param string     $repo_meta_api_access_token Access token for repo-meta API.
 * @param string     $repo_owner                 Repository owner.
 * @param string     $repo_name                  Repository name.
 *
 * @return null|array Results as array on success, null on failure.
 */
function vipgoci_repo_meta_api_data_fetch(
	string $repo_meta_api_base_url,
	int|string $repo_meta_api_user_id,
	string $repo_meta_api_access_token,
	string $repo_owner,
	string $repo_name
) {
	$cached_id = array(
		__FUNCTION__,
		$repo_meta_api_base_url,
		$repo_meta_api_user_id,
		$repo_meta_api_access_token,
		$repo_owner,
		$repo_name,
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching repository meta-data from repo-meta API' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_meta_api_base_url' => $repo_meta_api_base_url,
			'repo_meta_api_user_id'  => $repo_meta_api_user_id,
			'repo_owner'             => $repo_owner,
			'repo_name'              => $repo_name,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}

	$curl_retries = 0;

	do {
		$resp_data        = false;
		$resp_data_parsed = null;

		$endpoint_url =
			$repo_meta_api_base_url .
			'/v1' .
			'/sites?' .
			'active=1&' .
			'page=1&' .
			'pagesize=20&' .
			'source_repo=' . rawurlencode( $repo_owner . '/' . $repo_name );

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $endpoint_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, VIPGOCI_HTTP_API_LONG_TIMEOUT );

		curl_setopt(
			$ch,
			CURLOPT_USERAGENT,
			VIPGOCI_CLIENT_ID
		);

		$endpoint_send_headers = array();

		if ( ! empty( $repo_meta_api_user_id ) ) {
			$endpoint_send_headers[] =
				'API-User-ID: ' . $repo_meta_api_user_id;
		}

		if ( ! empty( $repo_meta_api_access_token ) ) {
			$endpoint_send_headers[] =
				'Access-Token: ' . $repo_meta_api_access_token;
		}

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			$endpoint_send_headers
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		vipgoci_curl_set_security_options(
			$ch
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'repo_meta_data_endpoint_api_request' );

		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'repo_meta_data_endpoint_api'
		);

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'repo_meta_data_endpoint_api_request' );

		if ( false !== $resp_data ) {
			$resp_data_parsed = json_decode(
				$resp_data,
				true
			);
		}

		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);

		if (
			( false === $resp_data ) ||
			( null === $resp_data_parsed ) ||
			(
				( isset( $resp_data_parsed['status'] ) ) &&
				( 'error' === $resp_data_parsed['status'] )
			)
		) {
			vipgoci_log(
				'Failed fetching or parsing data...',
				array(
					'resp_data'        => $resp_data,
					'resp_data_parsed' => $resp_data_parsed,
					'curl_error'       => curl_error( $ch ),
					'http_status'      => (
						isset( $resp_headers['status'] ) ?
						$resp_headers['status'] :
						null
					),
				)
			);

			/*
			 * For the while() loop below.
			 */
			if ( ! isset( $resp_data_parsed['status'] ) ) {
				$resp_data = false;
			}
		}

		curl_close( $ch );

	} while (
		( false === $resp_data ) &&
		( $curl_retries++ < 2 )
	);

	vipgoci_cache(
		$cached_id,
		$resp_data_parsed
	);

	return $resp_data_parsed;
}

/**
 * Fetch data from repo-meta API, then try
 * to match fields and their values with
 * the data. The fields and values are those
 * found in a particular $option parameter
 * specified as an argument here ($option_name).
 *
 * @param array  $options         Options needed.
 * @param string $option_name     Option name.
 * @param mixed  $option_no_match Variable to update when there is a match.
 *
 * @return bool Return true on match, otherwise return false.
 */
function vipgoci_repo_meta_api_data_match(
	array $options,
	string $option_name,
	mixed &$option_no_match
) :bool {
	if (
		( empty( $option_name ) ) ||
		( empty( $options['repo-meta-api-base-url'] ) ) ||
		( empty( $options[ $option_name ] ) )
	) {
		vipgoci_log(
			'Not attempting to match repo-meta API field-value ' .
				'to a criteria due to invalid configuration',
			array(
				'option_name'
					=> $option_name,

				'repo_meta_api_base_url'
					=> isset( $options['repo-meta-api-base-url'] ) ?
						$options['repo-meta-api-base-url'] : '',

				'repo_meta_match'
					=> ( ( ! empty( $option_name ) ) && ( isset( $options[ $option_name ] ) ) ) ?
						$options[ $option_name ] : '',
			)
		);

		return false;
	} else {
		vipgoci_log(
			'Attempting to match repo-meta API field-value to a criteria',
			array(
				'option_name'            => $option_name,
				'repo_meta_match'        => $options[ $option_name ],
				'repo_meta_api_base_url' => $options['repo-meta-api-base-url'],
			)
		);
	}

	$repo_meta_data = vipgoci_repo_meta_api_data_fetch(
		$options['repo-meta-api-base-url'],
		$options['repo-meta-api-user-id'],
		$options['repo-meta-api-access-token'],
		$options['repo-owner'],
		$options['repo-name']
	);

	if (
		( empty(
			$repo_meta_data['data']
		) )
		||
		( 'error' === $repo_meta_data['status'] )
	) {
		return false;
	}

	/*
	 * Loop through possible match in the
	 * option array -- bail out once we
	 * find a match.
	 */
	foreach (
		array_keys( $options[ $option_name ] ) as
			$option_name_key_no
	) {
		$found_fields = vipgoci_find_fields_in_array(
			$options[ $option_name ][ $option_name_key_no ],
			$repo_meta_data['data']
		);

		/*
		 * If we find one data-item that had
		 * all fields matching the criteria given,
		 * we return true.
		 */
		$ret_val = false;

		foreach (
			$found_fields as
				$found_field_item_key => $found_field_item_value
		) {
			if ( true === $found_field_item_value ) {
				$ret_val = true;
			}
		}

		if ( true === $ret_val ) {
			$option_no_match = $option_name_key_no;
			break;
		}
	}

	vipgoci_log(
		'Repo-meta API matching returning',
		array(
			'found_fields_in_repo_meta_data' => $found_fields,
			'repo_meta_data_item_cnt'        => count( $repo_meta_data['data'] ),
			'ret_val'                        => $ret_val,
			'option_no_match'                => $option_no_match,
		)
	);

	return $ret_val;
}

