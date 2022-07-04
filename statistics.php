<?php
/**
 * Statistics functions for vip-go-ci.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Initialize statistics array
 *
 * @param array $options        Options array.
 * @param array $prs_implicated Pull requests implicated by current commit.
 * @param array $results        Results of scanning array.
 *
 * @return void
 */
function vipgoci_stats_init(
	array $options,
	array $prs_implicated,
	array &$results
) :void {
	/*
	 * Init stats
	 */
	foreach ( $prs_implicated as $pr_item ) {
		/*
		 * Initialize array for stats and
		 * results of scanning, if needed.
		 */
		if ( empty( $results['issues'][ $pr_item->number ] ) ) {
			$results['issues'][ $pr_item->number ] = array();
		}

		if ( empty( $results[ VIPGOCI_SKIPPED_FILES ][ $pr_item->number ] ) ) {
			$results[ VIPGOCI_SKIPPED_FILES ][ $pr_item->number ] = array(
				'issues' => array(),
				'total'  => 0,
			);
		}

		foreach (
			array(
				VIPGOCI_STATS_PHPCS,
				VIPGOCI_STATS_LINT,
				VIPGOCI_STATS_WPSCAN_API,
			)
			as $stats_type
		) {
			/*
			 * Initialize stats for the stats-types only when
			 * supposed to run them
			 */
			if (
				( true !== $options[ $stats_type ] ) ||
				( ! empty( $results['stats'][ $stats_type ][ $pr_item->number ] ) )
			) {
				continue;
			}

			$results['stats'][ $stats_type ]
				[ $pr_item->number ] = array(
					'error'   => 0,
					'warning' => 0,
					'info'    => 0,
				);
		}
	}
}

/**
 * A simple function to keep record of how
 * much a time a particular action takes to execute.
 * Allows multiple records to be kept at the same time.
 *
 * Allows specifying 'start' acton, which indicates that
 * keeping record of measurement should start, 'stop'
 * which indicates that recording should be stopped,
 * and 'dump' which will return with an associative
 * array of all measurements collected henceforth.
 *
 * @param array|null $action Start or stop measuring, or dump measurements.
 * @param array|null $type   Measurement category.
 *
 * @return bool|array|int|float Boolean false on error, true when starting
 *                         measurement, int/float with time measured on stop,
 *                         array on dump.
 */
function vipgoci_runtime_measure(
	string|null $action = null,
	string|null $type = null
) :bool|array|int|float {
	static $runtime = array();
	static $timers  = array();

	/*
	 * Check usage.
	 */
	if (
		( VIPGOCI_RUNTIME_START !== $action ) &&
		( VIPGOCI_RUNTIME_STOP !== $action ) &&
		( VIPGOCI_RUNTIME_DUMP !== $action )
	) {
		return false;
	}

	// Dump all runtimes we have.
	if ( VIPGOCI_RUNTIME_DUMP === $action ) {
		/*
		 * Sort by value and maintain index association
		 */
		arsort( $runtime, SORT_NUMERIC );

		return $runtime;
	}

	/*
	 * Being asked to either start
	 * or stop collecting, act on that.
	 */

	if ( ! isset( $runtime[ $type ] ) ) {
		$runtime[ $type ] = 0;
	}

	if ( VIPGOCI_RUNTIME_START === $action ) {
		$timers[ $type ] = microtime( true );

		return true;
	} elseif ( VIPGOCI_RUNTIME_STOP === $action ) {
		if ( ! isset( $timers[ $type ] ) ) {
			return false;
		}

		$tmp_time = microtime( true ) - $timers[ $type ];

		$runtime[ $type ] += $tmp_time;

		unset( $timers[ $type ] );

		return $tmp_time;
	}
}

/**
 * A simple function to execute a utility and keep record of how much time
 * executing this utility takes. Attemps to run command again a few times if
 * execution fails. Will record status code and allows for standard error to be
 * returned as part of the output string.
 *
 * This function will use exec() to run the utility. The reasoning for the choice
 * is as follows:
 * - exec() will provide result code, unlike shell_exec(), system() and pcntl_exec()
 * - exec() will not direct the results to the user directly, unlike passthru()
 *
 * However, exec() will trim the output from the command executed, thus corrupting
 * it for many use-cases (such as git). To avoid this, this function will instruct
 * the shell to direct output to a temporary file, from where the output is read and
 * finally returned to the caller.
 *
 * @param string $cmd                             Command to execute.
 * @param array  $expected_result_code            Array of expected result codes.
 * @param string $res_output                      Output string (pointer).
 * @param int    $res_result_code                 Result code (pointer).
 * @param string $runtime_measure_type            Type of measurement to use.
 * @param bool   $catch_stderr                    If to include standard error (stderr) in output string (default false).
 * @param bool   $retry_on_unexpected_result_code If to retry when unexpected result code is observed (default false).
 * @param int    $exec_retry_max                  Number of times to retry execution of command in case exec() returns
 *                                                with false or unexpected result code is observed (when configured).
 *
 * @return string|null Output of command as string, or null on failure.
 */
function vipgoci_runtime_measure_exec_with_retry(
	string $cmd,
	array $expected_result_code,
	string &$res_output,
	int &$res_result_code,
	string $runtime_measure_type,
	bool $catch_stderr = false,
	bool $retry_on_unexpected_result_code = false,
	int $exec_retry_max = 2
): ?string {
	$exec_retry_cnt  = 0;
	$exec_retry_stop = false;

	do {
		// Create temporary file for output.
		$output_file = vipgoci_save_temp_file(
			'vipgoci-exec-output-',
			null,
			''
		);

		/*
		 * Output everything into file we read later.
		 *
		 * Reason: We cannot use the parameter exec() provides
		 * as the results will be trimmed (whitespaces removed),
		 * which means for many of our use-cases, the data is corrupt.
		 * Hence, we output to a temporary file and read it again
		 * afterwards.
		 *
		 * See: https://www.php.net/manual/en/function.exec.php
		 */
		$cmd_amended = $cmd . ' >' . $output_file;

		if ( true === $catch_stderr ) {
			$cmd_amended .= ' 2>&1 ';
		}

		if ( 0 < $exec_retry_cnt ) {
			/*
			 * If retrying, sleep one second just in
			 * case there is something temporary causing
			 * execution to fail.
			 */
			sleep( 1 );
		}

		vipgoci_log(
			( 0 === $exec_retry_cnt ) ?
				'Executing command...' :
				'Retrying execution of command...',
			array(
				'cmd_amended'          => $cmd_amended,
				'exec_retry_cnt'       => $exec_retry_cnt,
				'runtime_measure_type' => $runtime_measure_type,
				'expected_result_code' => $expected_result_code,
				'catch_stderr'         => $catch_stderr,
			),
			( 0 === $exec_retry_cnt ) ? 2 : 0,
			( 0 === $exec_retry_cnt ) ? false : true
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, $runtime_measure_type );

		$exec_result_return = exec(
			$cmd_amended,
			$exec_result_output,
			$exec_result_code,
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, $runtime_measure_type );

		/*
		 * Ensure temporary file is accessible only to owner. Shell
		 * will maintain permission but this is for extra safety.
		 */
		$chmod_res = chmod( $output_file, 0600 );

		if ( false === $chmod_res ) {
			vipgoci_log(
				'Unable to change permission of temporary file, unexpected error',
				array(
					'output_file' => $output_file,
				)
			);

			$exec_result_return = null;

			continue;
		}

		$res_result_code = $exec_result_code;
		$res_output      = null;

		if ( false === $exec_result_return ) {
			/*
			 * exec() returned with failure.
			 * We can retry in this case.
			 */
			$exec_result_return = null;
			$exec_retry_stop    = false;
		} elseif (
			( false !== $exec_result_return ) &&
			( 0 === strpos( $exec_result_return, 'sh: 1' ) ) &&
			( false !== strrpos( $exec_result_return, ': not found' ) )
		) {
			/*
			 * Shell returned with a file not found error.
			 * In those cases, set to null to avoid problems later.
			 * Ensure we retry only once.
			 */

			$exec_result_return = null;
			$exec_retry_stop    = false;

			if ( 1 < $exec_retry_max ) {
				$exec_retry_max = 1; // Retry only once more.
			}
		} elseif ( false === in_array(
			$exec_result_code,
			$expected_result_code,
			true
		) ) {
			/*
			 * Command was executed but returned with unexpected
			 * status code.
			 */
			$exec_result_return = null;

			if ( false === $retry_on_unexpected_result_code ) {
				// Do not retry in case of unexpected exit code.
				$exec_retry_stop = true;
			} else {
				$exec_retry_stop = false;
			}

			/*
			 * Read output from command and store.
			 *
			 * Do not change $exec_result_return as that
			 * should be null, indicating failure.
			 */
			$res_output = file_get_contents(
				$output_file
			);

			if ( false === $res_output ) {
				vipgoci_log(
					'Unable to read file with results from executing command',
					array(
						'exec_result_return' => $exec_result_return,
						'output_file'        => $output_file,
					)
				);

				$res_output = null;
			}
		} else {
			// Read output and store.
			$exec_result_return = file_get_contents(
				$output_file
			);

			if ( false === $exec_result_return ) {
				vipgoci_log(
					'Unable to read file with results from executing command',
					array(
						'exec_result_return' => $exec_result_return,
						'output_file'        => $output_file,
					)
				);

				$exec_result_return = null;
			} else {
				$res_output = $exec_result_return;
			}

			if ( ! empty( $exec_result_output ) ) {
				// All output should be in temporary file.
				vipgoci_sysexit(
					'Got non-empty output result from exec() parameter. This should not happen',
					array(
						'cmd_amended'        => $cmd_amended,
						'exec_result_output' => $exec_result_output,
						'exec_result_return' => $exec_result_return,
					),
					VIPGOCI_EXIT_SYSTEM_PROBLEM
				);
			}
		}

		unlink( $output_file );

		vipgoci_log(
			( null !== $exec_result_return ) ?
				'Successfully executed command' :
				'Could not execute command; exec() returned with failure',
			array(
				'cmd_amended'        => $cmd_amended,
				'exec_result_output' => $exec_result_output,
				'exec_result_return' => $exec_result_return,
				'exec_result_code'   => $exec_result_code,
				'exec_retry_stop'    => $exec_retry_stop,
			),
			( 0 === $exec_retry_cnt ) ? 2 : 0,
			( 0 === $exec_retry_cnt ) ? false : true
		);

	} while (
		( false === $exec_retry_stop ) &&
		( null === $exec_result_return ) &&
		( $exec_retry_max > 0 ) &&
		( ++$exec_retry_cnt <= $exec_retry_max )
	);

	/*
	 * Log if we retried executing command.
	 */
	if ( 0 < $exec_retry_cnt ) {
		vipgoci_log(
			( null === $exec_result_return ) ?
				'Failed to execute command' :
				'Retried executing command with success',
			array(
				'cmd'            => $cmd,
				'exec_retry_cnt' => $exec_retry_cnt,
			),
			0,
			true
		);
	}

	return $exec_result_return;
}

/**
 * Keep a counter of actions taken.
 * For instance, this can be used to keep
 * track of number of GitHub API requests.
 *
 * @param string|null $action Either increase counter or dump statistics.
 * @param string|null $type   Type of statistics.
 * @param int|null    $amount How much to increment.
 *
 * @return bool|array Boolean false on invalid action, boolean true on success, array when dumping statistics.
 */
function vipgoci_counter_report(
	string|null $action = null,
	string|null $type = null,
	int|null $amount = 1
) :bool|array {
	static $counters = array();

	/*
	 * Check usage.
	 */
	if (
		( VIPGOCI_COUNTERS_DO !== $action ) &&
		( VIPGOCI_COUNTERS_DUMP !== $action )
	) {
		return false;
	}

	// Dump all runtimes we have.
	if ( VIPGOCI_COUNTERS_DUMP === $action ) {
		return $counters;
	}

	/*
	 * Being asked to start
	 * collecting, act on that.
	 */
	if ( VIPGOCI_COUNTERS_DO === $action ) {
		if ( ! isset( $counters[ $type ] ) ) {
			$counters[ $type ] = 0;
		}

		$counters[ $type ] += $amount;

		return true;
	}
}

/**
 * Record statistics on number of linting and PHPCS
 * issues found in results.
 *
 * @param array $results Results of scanning.
 *
 * @return void
 */
function vipgoci_counter_update_with_issues_found(
	array $results
) :void {
	$stats_types = array_keys(
		$results['stats']
	);

	foreach ( $stats_types as $stat_type ) {
		/*
		 * Skip statistics for stat-types skipped
		 */
		if ( null === $results['stats'][ $stat_type ] ) {
			continue;
		}

		$pr_keys = array_keys(
			$results['stats'][ $stat_type ]
		);

		$max_issues_found = 0;

		foreach ( $pr_keys as $pr_key ) {
			$issue_types = array_keys(
				$results['stats'][ $stat_type ][ $pr_key ]
			);

			$issues_found = 0;

			foreach ( $issue_types as $issue_type ) {
				$issues_found +=
					$results['stats'][ $stat_type ][ $pr_key ][ $issue_type ];
			}

			$max_issues_found = max(
				$issues_found,
				$max_issues_found
			);
		}

		$stat_type = str_replace(
			'-',
			'_',
			$stat_type
		);

		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'github_pr_' . $stat_type . '_issues',
			$max_issues_found
		);
	}
}

/**
 * Keep statistics on number of files and lines
 * either scanned or linted.
 *
 * @param array  $options   Options needed.
 * @param string $file_name File to update statistics for.
 * @param string $stat_type Statistics type.
 *
 * @return void
 */
function vipgoci_stats_per_file(
	array $options,
	string $file_name,
	string $stat_type
) :void {
	$file_contents = vipgoci_gitrepo_fetch_committed_file(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$file_name,
		$options['local-git-repo']
	);

	if ( false === $file_contents ) {
		return;
	}

	$file_lines_cnt = count(
		explode(
			"\n",
			$file_contents
		)
	);

	vipgoci_counter_report(
		VIPGOCI_COUNTERS_DO,
		'github_pr_files_' . $stat_type,
		1
	);

	vipgoci_counter_report(
		VIPGOCI_COUNTERS_DO,
		'github_pr_lines_' . $stat_type,
		$file_lines_cnt
	);
}
