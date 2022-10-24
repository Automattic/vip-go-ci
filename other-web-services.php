<?php
/**
 * Other web services used by vip-go-ci.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Queue up a message for IRC API,
 * or alternatively empty the queue and
 * return its contents.
 *
 * @param string|null $message Message to add to queue, or null if to dump queue.
 * @param bool        $dump    Specify true if to dump queue.
 *
 * @return bool|array Returns array when dumping results, true when adding message to queue.
 */
function vipgoci_irc_api_alert_queue(
	string|null $message = null,
	bool $dump = false
) :bool|array {
	static $msg_queue = array();

	if ( true === $dump ) {
		$msg_queue_tmp = $msg_queue;

		$msg_queue = array();

		return $msg_queue_tmp;
	}

	$msg_queue[] = $message;

	return true;
}

/**
 * Remove any sections found in $message string bounded between the
 * VIPGOCI_IRC_IGNORE_STRING_START and VIPGOCI_IRC_IGNORE_STRING_END
 * constants.
 *
 * @param string $message Message string to filter.
 *
 * @return string Message with ignorable strings removed.
 */
function vipgoci_irc_api_filter_ignorable_strings(
	string $message
) :string {
	do {
		$ignore_section_start_pos = strpos( $message, VIPGOCI_IRC_IGNORE_STRING_START );

		if ( false !== $ignore_section_start_pos ) {
			$ignore_section_end_pos = strpos(
				$message,
				VIPGOCI_IRC_IGNORE_STRING_END,
				0 // From string start; needed for check below.
			);

			$ignore_section_start_pos_2 = strpos(
				$message,
				VIPGOCI_IRC_IGNORE_STRING_START,
				$ignore_section_start_pos + strlen( VIPGOCI_IRC_IGNORE_STRING_START ) // Needed for check below.
			);
		} else {
			$ignore_section_end_pos     = false;
			$ignore_section_start_pos_2 = false;
		}

		if (
			( false === $ignore_section_start_pos ) ||
			( false === $ignore_section_end_pos )
		) {
			// Neither string was found, stop processing here.
			continue;
		}

		if ( $ignore_section_end_pos <= $ignore_section_start_pos ) {
			// Invalid usage.
			vipgoci_log(
				'Incorrect usage of VIPGOCI_IRC_IGNORE_STRING_START and VIPGOCI_IRC_IGNORE_STRING_END; former should be placed before the latter',
				array(
					'message' => $message,
				),
				0
			);

			break;
		} elseif (
			( false !== $ignore_section_start_pos_2 ) &&
			( $ignore_section_end_pos > $ignore_section_start_pos_2 )
		) {
			// Invalid usage.
			vipgoci_log(
				'Incorrect usage of VIPGOCI_IRC_IGNORE_STRING_START and VIPGOCI_IRC_IGNORE_STRING_END; embedding one ignore string within another is not allowed',
				array(
					'message' => $message,
				),
				0
			);

			break;
		} elseif ( $ignore_section_end_pos > $ignore_section_start_pos ) {
			// Correct usage; end constant should always come after start constant.
			$message = substr_replace(
				$message,
				'',
				$ignore_section_start_pos,
				( $ignore_section_end_pos + strlen( VIPGOCI_IRC_IGNORE_STRING_END ) ) -
					$ignore_section_start_pos
			);
		}
	} while (
		( false !== $ignore_section_start_pos ) &&
		( false !== $ignore_section_end_pos )
	);

	return $message;
}

/**
 * Clean IRC ignorable constants away from message specified.
 *
 * Useful for functions submitting messages to GitHub, were the
 * constants should not be part of the HTML submitted.
 *
 * @param string $message Message to process.
 *
 * @return string Message with constants removed (if any).
 */
function vipgoci_irc_api_clean_ignorable_constants(
	string $message
) :string {
	return str_replace(
		array(
			VIPGOCI_IRC_IGNORE_STRING_START,
			VIPGOCI_IRC_IGNORE_STRING_END,
		),
		array(
			'',
			'',
		),
		$message
	);
}

/**
 * Make messages in IRC queue unique, but add
 * a prefix to those messages that were not unique
 * indicating how many they were.
 *
 * @param array $msg_queue Message queue.
 *
 * @return array New IRC queue, with prefixes as applicable.
 */
function vipgoci_irc_api_alert_queue_unique(
	array $msg_queue
) :array {
	$msg_queue_unique = array_unique(
		$msg_queue
	);

	/*
	 * If all messages were unique,
	 * nothing more to do.
	 */
	if (
		count( $msg_queue ) ===
		count( $msg_queue_unique )
	) {
		return $msg_queue;
	}

	/*
	 * Not all unique, count values
	 */
	$msg_queue_count = array_count_values(
		$msg_queue
	);

	$msg_queue_new = array();

	/*
	 * Add prefix where needed.
	 */
	foreach ( $msg_queue_count as $msg => $cnt ) {
		$msg_prefix = '';

		if ( $cnt > 1 ) {
			$msg_prefix = '(' . $cnt . 'x) ';
		}

		$msg_queue_new[] = $msg_prefix . $msg;
	}

	return $msg_queue_new;
}

/**
 * Empty IRC message queue and send off
 * to the IRC API.
 *
 * @param string $irc_api_url   URL to IRC API.
 * @param string $irc_api_token Access token to IRC API.
 * @param string $botname       Name of IRC bot.
 * @param string $channel       Channel to post to.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_irc_api_alerts_send(
	string $irc_api_url,
	string $irc_api_token,
	string $botname,
	string $channel
) :void {
	// Get IRC message queue.
	$msg_queue = vipgoci_irc_api_alert_queue(
		null,
		true
	);

	// Filter away removable strings.
	$msg_queue = array_map(
		'vipgoci_irc_api_filter_ignorable_strings',
		$msg_queue
	);

	// Ensure all strings we log are unique; if not make unique and add prefix.
	$msg_queue = vipgoci_irc_api_alert_queue_unique(
		$msg_queue
	);

	vipgoci_log(
		'Sending messages to IRC API',
		array(
			'msg_queue' => $msg_queue,
		)
	);

	foreach ( $msg_queue as $message ) {
		$irc_api_postfields = array(
			'message' => $message,
			'botname' => $botname,
			'channel' => $channel,
		);

		$ch = curl_init();

		curl_setopt(
			$ch,
			CURLOPT_URL,
			$irc_api_url
		);

		curl_setopt(
			$ch,
			CURLOPT_RETURNTRANSFER,
			1
		);

		curl_setopt(
			$ch,
			CURLOPT_CONNECTTIMEOUT,
			VIPGOCI_HTTP_API_SHORT_TIMEOUT
		);

		curl_setopt(
			$ch,
			CURLOPT_USERAGENT,
			VIPGOCI_CLIENT_ID
		);

		curl_setopt(
			$ch,
			CURLOPT_POST,
			1
		);

		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			json_encode( $irc_api_postfields )
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array( 'Authorization: Bearer ' . $irc_api_token )
		);

		vipgoci_curl_set_security_options(
			$ch
		);

		/*
		 * Execute query, keep record of how long time it
		 * took, and keep count of how many requests we do.
		 */

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'irc_api_post' );

		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'irc_api_request_post',
			1
		);

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'irc_api_post' );

		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);

		curl_close( $ch );

		/*
		 * Enforce a small wait between requests.
		 */

		time_nanosleep( 0, 500000000 );
	}
}

/**
 * Send statistics to pixel API so
 * we can keep track of actions we
 * take during runtime.
 *
 * @param string $pixel_api_url        URL to Pixel API.
 * @param array  $stat_names_to_report Statistics to report, both repository specific and global. Should be associative array.
 * @param array  $statistics           Statistics array, associative array of keys and values.
 *
 * @return void
 */
function vipgoci_send_stats_to_pixel_api(
	string $pixel_api_url,
	array $stat_names_to_report,
	array $statistics
) :void {
	vipgoci_log(
		'Sending statistics to pixel API service',
		array(
			'stat_names_to_report' => $stat_names_to_report,
		)
	);

	$stat_names_to_groups = array();

	foreach (
		array_keys( $stat_names_to_report ) as
			$statistic_group
	) {
		foreach (
			$stat_names_to_report[ $statistic_group ] as $stat_name
		) {
			$stat_names_to_groups[ $stat_name ][] = $statistic_group;
		}
	}

	foreach (
		$statistics as
			$stat_name => $stat_value
	) {
		/*
		 * We are to report only certain
		 * values, so skip those who we should
		 * not report on.
		 */
		if ( ! isset(
			$stat_names_to_groups[ $stat_name ]
		) ) {
			/*
			 * Not found, so nothing to report, skip.
			 */
			continue;
		}

		/*
		 * Do not report zero or lower.
		 */
		if ( 0 >= $stat_value ) {
			continue;
		}

		/*
		 * Report statistic if it belongs to one of the groups.
		 */
		foreach (
			$stat_names_to_groups[ $stat_name ] as $group_name
		) {
			/*
			 * Compose URL.
			 */
			$url = $pixel_api_url .
				'?' .
				'v=wpcom-no-pv' .
				'&' .
				'x_' . rawurlencode( strtolower( $group_name ) ) .
				'/' . rawurlencode( strtolower( $stat_name ) ) .
				'=' . rawurlencode( (string) $stat_value );

			/*
			 * Call service, log if request failed.
			 * Specify a short timeout, retry only once.
			 */
			$ret = vipgoci_http_api_fetch_url(
				$url,
				null, // No token needed.
				false, // No fatal error when request fails.
				1 // Retry once upon failure.
			);

			if ( null === $ret ) {
				vipgoci_log(
					'Unable to send data to Pixel API service',
					array(),
					0,
					true // Send to IRC.
				);
			}

			/*
			 * Sleep a short while between
			 * requests.
			 */
			time_nanosleep(
				0,
				500000000
			);
		}
	}

	vipgoci_log(
		'Finished sending statistics to pixel API service',
		array()
	);
}

