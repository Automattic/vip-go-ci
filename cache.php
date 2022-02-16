<?php
/**
 * Caching logic for vip-go-ci. Stores data in-memory
 * during run-time.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Get a specific item from in-memory cache based on
 * $cache_id_arr if $data is null, or if $data is not null,
 * add a specific item to cache.
 *
 * The data is stored in an associative array, with
 * key being an array (or anything else) -- $cache_id_arr --,
 * and used to identify the data up on retrieval.
 *
 * If the data being cached is an object, we make a copy of it,
 * and then store it. When the cached data is being retrieved,
 * we return a copy of the cached data.
 *
 * @param array|string $cache_id_arr Cache ID to use when caching data, or ask for data. Special string used to clear cache.
 * @param mixed        $data         Data to cache, null if asking for data.
 *
 * @return mixed Newly or previously cached data on success, false when no data
 *               is available, true on successful caching.
 */
function vipgoci_cache(
	array|string $cache_id_arr,
	mixed $data = null
) :mixed {
	global $vipgoci_cache_buffer;

	/*
	 * Special invocation: Allow for
	 * the cache to be cleared.
	 */
	if (
		( is_string(
			$cache_id_arr
		) )
		&&
		(
			VIPGOCI_CACHE_CLEAR ===
			$cache_id_arr
		)
	) {
		$vipgoci_cache_buffer = array();

		return true;
	}

	$cache_id = json_encode(
		$cache_id_arr
	);

	if ( null === $data ) {
		// Asking for data from cache, find and return if it exists.
		if ( isset( $vipgoci_cache_buffer[ $cache_id ] ) ) {
			$ret = $vipgoci_cache_buffer[ $cache_id ];

			// If an object, copy and return the copy.
			if ( is_object( $ret ) ) {
				$ret = clone $ret;
			}

			return $ret;
		} else {
			return false;
		}
	} else {
		/*
		 * Asking to save data in cache; save it and return the data.
		 */

		// If an object, copy, save it, and return the copy.
		if ( is_object( $data ) ) {
			$data = clone $data;
		}

		$vipgoci_cache_buffer[ $cache_id ] = $data;

		return $data;
	}
}

/**
 * Support function for other functions
 * that use the internal cache and need to indicate
 * that information from the cache was used.
 *
 * @param mixed $cache_used If this evaluates to true, will return string
 *                          indicating that cache was used, else empty string. 
 *
 * @return string Indication of cache usage.
 */
function vipgoci_cached_indication_str(
	mixed $cache_used
) :string {
	return $cache_used ? ' (cached)' : '';
}

