<?php
/**
 * WPScan API scanning logic for vip-go-ci.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Get list of paths to directories containing plugins or themes which were
 * altered by pull requests implicated by current commit and fall
 * within those paths that should be scanned using WPScan API. Paths
 * are relative to base of repository.
 *
 * Note: Files are included in results across all pull requests; hence
 * a file may be altered in context of one pull request and not another,
 * yet it will appear in results.
 *
 * @param array $options                        Options array for the program.
 * @param array $commit_skipped_files           Information about skipped files (reference).
 * @param array $files_affected_by_commit_by_pr Files affected by commit by pull request.
 *
 * @return null|array Null when no altered files were identified. Otherwise, array with paths to specific plugin/theme directories which may have been altered by any of the pull requests.
 */
function vipgoci_wpscan_find_addon_dirs_altered(
	array $options,
	array &$commit_skipped_files,
	array $files_affected_by_commit_by_pr
) :null|array {
	if ( empty( $files_affected_by_commit_by_pr['all'] ) ) {
		vipgoci_log(
			'No plugins/themes found to scan via WPScan API (empty list)',
			array(
				'repo_owner'                          => $options['repo-owner'],
				'repo_name'                           => $options['repo-name'],
				'commit'                              => $options['commit'],
				'files_affected_by_commit_by_pr[all]' => $files_affected_by_commit_by_pr['all'],
			),
			0,
			true // Log to IRC.
		);

		return null;
	}

	/*
	 * Loop through all files affected by the commit,
	 * irrespective of pull request, and try to determine
	 * if an affected file is located in a path that
	 * should be scanned and is thereby eligible
	 * for scan using WPScan API.
	 */

	// First, construct unique list of directories with files affected.
	$directories_changed_by_commit = array();

	foreach ( $files_affected_by_commit_by_pr['all'] as $file_name ) {
		// Directory name where file is located.
		$file_dir = dirname( $file_name );

		vipgoci_array_push_uniquely(
			$directories_changed_by_commit,
			$file_dir
		);
	}

	unset( $file_dir );

	/*
	 * Second, determine directories to scan -- only scan
	 * those that are found in $options['wpscan-api-paths'].
	 */
	$addon_dirs_relevant_to_scan = array();

	foreach (
		$directories_changed_by_commit as $directory_changed_by_commit
	) {
		foreach ( $options['wpscan-api-paths'] as $wpscan_path ) {
			/*
			 * Ensure we collect only base directory of plugins or themes,
			 * not subdirectories. Also add $wpscan_path if a plugin or theme
			 * is found there.
			 */
			$dir_changed_by_commit_relative = vipgoci_directory_path_get_dir_and_include_base(
				$wpscan_path,
				$directory_changed_by_commit
			);

			// Perform sanity checks before adding to results.
			if (
				( $wpscan_path === $directory_changed_by_commit ) &&
				( null === $dir_changed_by_commit_relative ) &&
				( true === file_exists(
					$options['local-git-repo'] . DIRECTORY_SEPARATOR .
						$wpscan_path
				) )
			) {
				// Addon file found in root of plugin/theme directory, add directory.
				vipgoci_array_push_uniquely(
					$addon_dirs_relevant_to_scan,
					$wpscan_path
				);

				continue;
			}

			if ( empty( $dir_changed_by_commit_relative ) ) {
				continue;
			}

			// Ensure file/directory exists before adding to results.
			if ( true !== file_exists(
				$options['local-git-repo'] . DIRECTORY_SEPARATOR .
					$dir_changed_by_commit_relative
			) ) {
				continue;
			}

			vipgoci_array_push_uniquely(
				$addon_dirs_relevant_to_scan,
				$dir_changed_by_commit_relative
			);

			break;
		}
	}

	vipgoci_log(
		( empty( $addon_dirs_relevant_to_scan ) ) ?
			'No plugins/themes found to scan via WPScan API' :
			'Found plugins/themes to scan via WPScan API',
		array(
			'repo_owner'                  => $options['repo-owner'],
			'repo_name'                   => $options['repo-name'],
			'wpscan_paths'                => $options['wpscan-api-paths'],
			'addon_dirs_relevant_to_scan' => $addon_dirs_relevant_to_scan,
		),
		0,
		true // Log to IRC.
	);

	if ( empty( $addon_dirs_relevant_to_scan ) ) {
		// No plugins/themes found, do not continue.
		return null;
	}

	return $addon_dirs_relevant_to_scan;
}

/**
 * Get slugs and data for add-ons found in $addon_dirs_relevant_to_scan and ensure all
 * add-ons found can be associated with changes in code. Those that cannot will be removed
 * from the resulting array. This ensures only add-ons added or modified are scanned and
 * reported about (if applicable), especially those placed within directories belonging
 * to other add-ons.
 *
 * @param array $options                        Options array for the program.
 * @param array $addon_dirs_relevant_to_scan    Array of directories which contain plugins/themes altered to be scanned.
 * @param array $files_affected_by_commit_by_pr Files affected by commit by pull request.
 *
 * @return Array Directories which contain plugins/themes altered to be scanned, with irrelevant ones removed.
 */
function vipgoci_wpscan_get_altered_addons_data_and_slugs(
	array $options,
	array $addon_dirs_relevant_to_scan,
	array $files_affected_by_commit_by_pr
) :array {
	$addon_data_and_slugs_for_addon_dirs = array();

	foreach ( $addon_dirs_relevant_to_scan as $addon_dir_relevant ) {
		$known_addons             = array();
		$known_addons_file_to_key = array();

		$addon_data_for_dir = vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory(
			$options['local-git-repo'] . DIRECTORY_SEPARATOR . $addon_dir_relevant,
			$options['wpscan-api-plugin-file-extensions'],
			$options['wpscan-api-theme-file-extensions'],
			( ! in_array( $addon_dir_relevant, $options['wpscan-api-paths'], true ) )
		);

		// Construct list of known add-ons.
		foreach ( $addon_data_for_dir as $addon_item_key => $addon_item_info ) {
			$path = str_replace(
				$options['local-git-repo'] . '/',
				'',
				$addon_item_info['file_name']
			);

			$known_addons[] = $path;

			$known_addons_file_to_key[ $path ] = $addon_item_key;
		}

		// Get add-ons that do not match code changes.
		$addons_not_matched = vipgoci_wpcore_misc_get_addons_not_altered(
			$options,
			$known_addons,
			$files_affected_by_commit_by_pr
		);

		// Remove non-matched add-ons from results.
		foreach ( $addons_not_matched as $addon_not_matched_path ) {
			unset( $addon_data_for_dir[ $known_addons_file_to_key[ $addon_not_matched_path ] ] );
		}

		$addon_data_and_slugs_for_addon_dirs[ $addon_dir_relevant ] = $addon_data_for_dir;
	}

	return $addon_data_and_slugs_for_addon_dirs;
}

/**
 * Loop through plugin/theme directories altered by pull request and use
 * the information look for any plugins/themes that are obsolete or have
 * vulnerabilities via the WPScan API. Save results for further processing.
 *
 * @param array $options                             Options array for the program.
 * @param array $addon_dirs_relevant_to_scan         Array of directories which contain plugins/themes altered to be scanned.
 * @param array $addon_data_and_slugs_for_addon_dirs Array with slugs and other information for addon directories.
 *
 * @return array Associative array with results. For example:
 *   Array(
 *     [plugins/my-plugin] => Array(
 *       [plugin.php] => Array(
 *         [type] => obsolete
 *         [wpscan_results] => Array(
 *           [friendly_name] => My plugin
 *           [latest_version] => 1.0.0
 *           [last_updated] => 2022-03-15T01:29:00.000Z
 *           [popular] => 1
 *           [vulnerabilities] => Array()
 *         )
 *         [addon_data_for_dir] => Array(
 *           [type] => vipgoci-addon-plugin
 *           [addon_headers] => Array(
 *             [Name] => My plugin
 *             [PluginURI] => http://wordpress.org/plugins/my-plugin
 *             [Version] => 1.0.0
 *             [Description] => This is example plugin description.
 *             [Author] => Author Name
 *             [Title] => My plugin
 *             [AuthorName] => Author Name
 *             ...
 *           )
 *           [name] => My plugin
 *           [version_detected] => 0.9
 *           [file_name] => /tmp/my-repo/plugins/my-plugin/plugin.php
 *           [slug] => my-plugin
 *           [new_version] => 1.0.0
 *           [package] => https://downloads.wordpress.org/plugin/my-plugin.1.0.0.zip
 *           [url] => https://wordpress.org/plugins/my-plugin/
 *         )
 *       )
 *     )
 *  )
 */
function vipgoci_wpscan_scan_dirs_altered(
	array $options,
	array $addon_dirs_relevant_to_scan,
	array $addon_data_and_slugs_for_addon_dirs
) :array {
	$problematic_addons_found = array();

	foreach ( $addon_dirs_relevant_to_scan as $addon_dir_relevant ) {
		$addon_data_for_dir = $addon_data_and_slugs_for_addon_dirs[ $addon_dir_relevant ];

		foreach ( $addon_data_for_dir as $addon_item_key => $addon_item_info ) {
			$addon_item_key = str_replace(
				array( VIPGOCI_ADDON_PLUGIN . '-', VIPGOCI_ADDON_THEME . '-' ),
				array( '', '' ),
				$addon_item_key
			);

			if ( empty( $addon_item_info['slug'] ) ) {
				continue;
			}

			/*
			 * Begin with collecting statistics on number of lines
			 * and files we scan.
			 */
			vipgoci_stats_per_file(
				$options,
				str_replace(
					$options['local-git-repo'],
					'',
					$addon_item_info['file_name']
				),
				'wpscan_api_scanned'
			);

			/*
			 * Next, call WPScan API to get security
			 * vulnerability information. Along the way,
			 * we get latest version available.
			 */
			$wpscan_results = vipgoci_wpscan_do_scan_via_api(
				$addon_item_info['slug'],
				$addon_item_info['type'],
				$options['wpscan-api-token']
			);

			if ( empty(
				$wpscan_results[ $addon_item_info['slug'] ]
			) ) {
				vipgoci_log(
					'Unable to get information from WPScan API about slug',
					array(
						'slug' => $addon_item_info['slug'],
					),
					0,
					true // Log to IRC.
				);

				continue;
			}

			// Filter away vulnerabilities that have been fixed in the observed version.
			$wpscan_results = vipgoci_wpscan_filter_fixed_vulnerabilities(
				$addon_item_info['slug'],
				$addon_item_info['version_detected'],
				$wpscan_results
			);

			/*
			 * Find out if addon is obsolete and if it has any
			 * security vulnerabilities.
			 */
			$addon_obsolete = version_compare(
				$wpscan_results[ $addon_item_info['slug'] ]['latest_version'],
				$addon_item_info['version_detected'],
				'>'
			);

			$addon_security_vulnerabilities = ( ! empty(
				$wpscan_results[ $addon_item_info['slug'] ]['vulnerabilities']
			) );

			/*
			 * Process information collected, determine type.
			 */
			if (
				( false === $addon_obsolete ) &&
				( false === $addon_security_vulnerabilities )
			) {
				/*
				 * If plugin is current and has no security
				 * vulnerability, do nothing.
				 */
				continue;
			} elseif (
				( true === $addon_obsolete ) &&
				( false === $addon_security_vulnerabilities )
			) {
				/*
				 * If plugin/theme is obsolete but no
				 * vulnerabilites are noted, add to array
				 * of obsolete but not vulnerable
				 * plugins/themes.
				 */
				$problematic_addons_found[ $addon_dir_relevant ][ $addon_item_key ] = array(
					'slug'               => $addon_item_info['slug'],
					'security_type'      => VIPGOCI_WPSCAN_OBSOLETE,
					'wpscan_results'     => $wpscan_results[ $addon_item_info['slug'] ],
					'addon_data_for_dir' => $addon_item_info,
				);
			} elseif ( true === $addon_security_vulnerabilities ) {
				/*
				 * If current or obsolete plugin, and is
				 * vulnerable, then add to vulnerable addons.
				 */
				$problematic_addons_found[ $addon_dir_relevant ][ $addon_item_key ] = array(
					'slug'               => $addon_item_info['slug'],
					'security_type'      => VIPGOCI_WPSCAN_VULNERABLE,
					'wpscan_results'     => $wpscan_results[ $addon_item_info['slug'] ],
					'addon_data_for_dir' => $addon_item_info,
				);
			}
		}
	}

	/*
	 * Return information about addons collected.
	 */
	return $problematic_addons_found;
}

/**
 * Add information about plugins or themes which
 * are vulnerable or obsolete to results array.
 *
 * @param array $options                        Options array for the program.
 * @param array $commit_issues_submit           Results array for WPScan API scanning (reference).
 * @param array $commit_issues_stats            Result statistics for WPScan API scanning (reference).
 * @param array $commit_skipped_files           Information about skipped files (reference).
 * @param array $files_affected_by_commit_by_pr Files affected by commit by pull request.
 * @param array $problematic_addons_found       Array with problematic addons found, should include local information and WPScan API information.
 *
 * @return void
 */
function vipgoci_wpscan_scan_save_for_submission(
	array $options,
	array &$commit_issues_submit,
	array &$commit_issues_stats,
	array &$commit_skipped_files,
	array $files_affected_by_commit_by_pr,
	array $problematic_addons_found
) :void {
	vipgoci_log(
		'Adding into results information about vulnerable/obsolete plugins/themes ' .
			'gathered via local scanning, WPScan API and WordPress.org API',
		array(
			'repo_owner'               => $options['repo-owner'],
			'repo_name'                => $options['repo-name'],
			'wpscan_paths'             => $options['wpscan-api-paths'],
			'problematic_addons_found' => $problematic_addons_found,
		),
		2
	);

	$pr_labels_found_and_reported = array();

	/*
	 * Loop through each plugin/theme that is vulnerable/obsolete;
	 * key is the base path and value is information about each plugin/theme.
	 */
	foreach (
		$problematic_addons_found as
			$dir_with_problem_addons => $problem_addon_files
	) {
		// Loop through each file.
		foreach (
			$problem_addon_files as
				$problem_addon_file_name => $problems_in_addon_file
		) {
			/*
			 * Loop through each pull request; we need to
			 * assign result to submit for each applicable pull request.
			 */
			foreach (
				$files_affected_by_commit_by_pr as
					$pr_number => $pr_changed_files
			) {
				if ( 'all' === $pr_number ) {
					// Ignore the special 'all' key.
					continue;
				}

				/*
				 * Check if to skip posting results according to label.
				 */
				$pr_label_skip_wpscan = vipgoci_github_pr_labels_get(
					$options['repo-owner'],
					$options['repo-name'],
					$options['token'],
					$pr_number,
					VIPGOCI_WPSCAN_SKIP_SCAN_PR_LABEL
				);

				if (
					( ! empty( $pr_label_skip_wpscan ) ) &&
					( ! isset( $pr_labels_found_and_reported[ $pr_number ] ) )
				) {
					/*
					 * Skip scanning requested; do not save results, log.
					 */
					vipgoci_log(
						'Label on pull request indicated ' .
						'to skip WPScan API scanning; not ' .
						'posting results',
						array(
							'repo_owner'           => $options['repo-owner'],
							'repo_name'            => $options['repo-name'],
							'commit_id'            => $options['commit'],
							'pr_number'            => $pr_number,
							'pr_label_skip_wpscan' => $pr_label_skip_wpscan,
						)
					);

					$pr_labels_found_and_reported[ $pr_number ] = true;

					continue;
				} elseif (
					( ! empty( $pr_label_skip_wpscan ) ) &&
					( isset( $pr_labels_found_and_reported[ $pr_number ] ) )
				) {
					/*
					 * Skip scanning requested. Don't log again.
					 */
					continue;
				}

				/*
				 * Ensure we report only about directories
				 * which are in list of changed files.
				 */
				$should_add_file = vipgoci_directory_found_in_file_list(
					$pr_changed_files,
					$dir_with_problem_addons
				);

				if ( true === $should_add_file ) {
					$level = VIPGOCI_WPSCAN_VULNERABLE === $problem_addon_files[ $problem_addon_file_name ]['security_type'] ?
						VIPGOCI_ISSUE_TYPE_ERROR : VIPGOCI_ISSUE_TYPE_WARNING;

					$issue_details = $problem_addon_files[ $problem_addon_file_name ];

					// Calculate severity level.
					$issue_severity = VIPGOCI_WPSCAN_VULNERABLE === $problem_addon_files[ $problem_addon_file_name ]['security_type'] ?
						10 : 7;

					// Determine installed location.
					$addon_installed_location = $dir_with_problem_addons;

					$commit_issues_submit[ $pr_number ][] = array(
						'type'      => VIPGOCI_STATS_WPSCAN_API,
						'file_name' => $dir_with_problem_addons . DIRECTORY_SEPARATOR . $problem_addon_file_name, // Required field.
						'file_line' => 1, // Required field, even if not used.
						'issue'     => array(
							'addon_type' => $issue_details['addon_data_for_dir']['type'],
							'message'    => $problem_addon_files[ $problem_addon_file_name ]['wpscan_results']['friendly_name'],
							'level'      => $level,
							'security'   => $problem_addon_files[ $problem_addon_file_name ]['security_type'],
							'severity'   => $issue_severity,
							'details'    => array(
								'slug'                => $issue_details['slug'],
								'url'                 => $issue_details['addon_data_for_dir']['url'],
								'installed_location'  => $addon_installed_location,
								'version_detected'    => $issue_details['addon_data_for_dir']['version_detected'],
								'latest_version'      => $issue_details['wpscan_results']['latest_version'],
								'latest_download_uri' => $issue_details['addon_data_for_dir']['package'],
								'vulnerabilities'     => $issue_details['wpscan_results']['vulnerabilities'],
							),
						),
					);

					/*
					 * Collect statistics on
					 * number of warnings/errors
					 */
					$commit_issues_stats[ $pr_number ][ $level ]++;
				}
			}
		}
	}
}

/**
 * Scan pull requests associated with the commit specified in $options['commit']
 * for any insecure or obsolete plugins or themes using the WPScan API. Information
 * will be collected via the WordPress.org API as well -- in particular, slugs.
 *
 * @param array $options              Options array for the program.
 * @param array $commit_issues_submit Results for WPScan API scanning (reference).
 * @param array $commit_issues_stats  Result statistics for WPScan API scanning (reference).
 * @param array $commit_skipped_files Information about skipped files (reference).
 *
 * @return void
 */
function vipgoci_wpscan_scan_commit(
	array $options,
	array &$commit_issues_submit,
	array &$commit_issues_stats,
	array &$commit_skipped_files
) :void {
	if ( false === $options['wpscan-api'] ) {
		vipgoci_log(
			'Will not scan added/updated themes/plugins using WPScan API, not configured to do so',
			array(
				'repo_owner' => $options['repo-owner'],
				'repo_name'  => $options['repo-name'],
				'commit_id'  => $options['commit'],
			)
		);

		return;
	} else {
		vipgoci_log(
			'About to scan added/updated themes/plugins using WPScan API',
			array(
				'repo_owner'             => $options['repo-owner'],
				'repo_name'              => $options['repo-name'],
				'commit_id'              => $options['commit'],
				'plugin-file-extensions' => $options['wpscan-api-plugin-file-extensions'],
				'theme-file-extensions'  => $options['wpscan-api-theme-file-extensions'],
			)
		);
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'wpscan_scan_commit' );

	/*
	 * Get list of all files affected by
	 * pull requests implicated by the commit.
	 */
	$files_affected_by_commit_by_pr = vipgoci_github_files_affected_by_commit(
		$options,
		$options['commit'],
		$commit_skipped_files,
		true,
		false, // Do not give list of removed files.
		true,
		array(
			'skip_folders' => $options['wpscan-api-skip-folders'],
		),
		false
	);

	/*
	 * Get paths to added/altered plugins or themes.
	 * Paths are relative to base of repository.
	 */

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'wpscan_find_addon_dirs_altered' );

	$addon_dirs_relevant_to_scan = vipgoci_wpscan_find_addon_dirs_altered(
		$options,
		$commit_skipped_files,
		$files_affected_by_commit_by_pr
	);

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'wpscan_find_addon_dirs_altered' );

	if ( null === $addon_dirs_relevant_to_scan ) {
		// No paths found, do not continue.
		vipgoci_log(
			'Will not scan added/updated themes/plugins using WPScan API, no plugins/themes added/updated',
			array(
				'repo_owner'             => $options['repo-owner'],
				'repo_name'              => $options['repo-name'],
				'commit_id'              => $options['commit'],
				'plugin-file-extensions' => $options['wpscan-api-plugin-file-extensions'],
				'theme-file-extensions'  => $options['wpscan-api-theme-file-extensions'],
			)
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'wpscan_scan_commit' );

		return;
	}

	/*
	 * Associate all changed files with add-ons found,
	 * remove any add-ons that cannot be associated
	 * successfully.
	 */
	$addon_data_and_slugs_for_addon_dirs = vipgoci_wpscan_get_altered_addons_data_and_slugs(
		$options,
		$addon_dirs_relevant_to_scan,
		$files_affected_by_commit_by_pr
	);

	/*
	 * Scan all directories with added/altered plugins or themes.
	 */
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'wpscan_scan_get_addon_data_and_slugs_for_directories' );

	$problematic_addons_found = vipgoci_wpscan_scan_dirs_altered(
		$options,
		$addon_dirs_relevant_to_scan,
		$addon_data_and_slugs_for_addon_dirs
	);

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'wpscan_scan_get_addon_data_and_slugs_for_directories' );

	/*
	 * Save results for submission later.
	 */
	vipgoci_wpscan_scan_save_for_submission(
		$options,
		$commit_issues_submit,
		$commit_issues_stats,
		$commit_skipped_files,
		$files_affected_by_commit_by_pr,
		$problematic_addons_found
	);

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'wpscan_scan_commit' );

	vipgoci_log(
		'Scanning via WPScan API complete',
		array()
	);
}

