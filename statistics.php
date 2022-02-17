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
				VIPGOCI_STATS_HASHES_API,
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
 * A simple function to keep record of how
 * much time executing a particular command takes.
 *
 * @param string $cmd                  Shell command to execute.
 * @param string $runtime_measure_type Type of measurement to use.
 *
 * @return string Output of command.
 */
function vipgoci_runtime_measure_shell_exec(
	string $cmd,
	string $runtime_measure_type = null
): ?string {
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, $runtime_measure_type );

	$shell_exec_output = shell_exec( $cmd );

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, $runtime_measure_type );

	return $shell_exec_output;
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
