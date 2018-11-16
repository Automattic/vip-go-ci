<?php

/*
 * Scan a SVG-file for disallowed
 * tokens. Will return results in the
 * same format as PHPCS does.
 *
 * Note that this function is designed as
 * a substitute for PHPCS in case of
 * scanning SVG files.
 */
function vipgoci_svg_scan_single_file(
	$options,
	$file_name
) {
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'svg_scan_single_file' );

	vipgoci_log(
		'Scanning single SVG file',
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'commit_id'	=> $options['commit'],
			'svg_checks'	=> $options['svg-checks'],
			'file_name'	=> $file_name,
		)
	);

	/*
	 * These tokens are not allowed
	 * in SVG files. Note that we do
	 * a case insensitive search for these.
	 */

	$disallowed_tokens = array(
		'<?php',
		'<?=',
		'<script ',
	);

	/*
	 * Read in file contents from Git repo.
	 */

	$file_contents = vipgoci_gitrepo_fetch_committed_file(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$file_name,
		$options['local-git-repo']
	);

	/*
	 * Determine file-ending of the file,
	 * then save it into temporary file
	 * before scanning.
	 */

	$file_extension = vipgoci_file_extension(
		$file_name
	);

	/*
	 * Could not determine? Return null.
	 * We only process SVG files.
	 */
	if ( 'svg' !== $file_extension ) {

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'svg_scan_single_file' );

		vipgoci_log(
			'Could not scan file, does not seem to be a SVG file',
			array(
				'repo_owner'	=> $options['repo-owner'],
				'repo_name'	=> $options['repo-name'],
				'commit_id'	=> $options['commit'],
				'svg_checks'	=> $options['svg-checks'],
				'file_name'	=> $file_name,
			)
		);


		return null;
	}

	$temp_file_name = vipgoci_save_temp_file(
		'svg-scan-',
		$file_extension,
		$file_contents
	);

	$file_contents = file_get_contents(
		$temp_file_name
	);

	unlink( $temp_file_name );

	/*
	 * Explode each line into
	 * each item in an array.
	 */
	$file_lines_arr = explode(
		PHP_EOL,
		$file_contents
	);

	/*
	 * Array for scanning results,
	 * line counter.
	 */
	$results_files = array();

	$line_no = 1; // Line numbers begin at 1

	/*
	 * Loop through each line of the
	 * file, look for disallowed tokens,
	 * record any found and keep statistics.
	 */
	foreach ( $file_lines_arr as $file_line_item ) {
		/*
		 * Prepare results array, assume nothing
		 * is wrong until proven otherwise.
		 */
		if ( ! isset( $results_files[ $temp_file_name ] ) ) {
			$results_files[ $temp_file_name ] = array(
				'errors'	=> 0,
				'warnings'	=> 0,
				'fixable'	=> 0,
				'messages'	=> array(),
			);
		}

		/*
		 * Scan for each disallowed token
		 */
		foreach( $disallowed_tokens as $disallowed_token ) {
			/*
			 * Do a case insensitive search
			 */
			$token_pos = stripos(
				$file_line_item,
				$disallowed_token
			);

			if ( false === $token_pos ) {
				continue;
			}

			/*
			 * Found a problem, adding to results.
			 */

			$results_files[ $temp_file_name ]['errors']++;

			$results_files[ $temp_file_name ]['messages'][] =
				array(
					'message'	=>
						'Found forbidden tag in SVG ' .
							'file: \'' .
							$disallowed_token .
							'\'',

					'source'	=>
						'WordPressVIPMinimum.' .
						'Security.SVG.DisallowedTags',

					'severity'	=> 5,
					'fixable'	=> false,
					'type'		=> 'ERROR',
					'line'		=> $line_no,
					'column'	=> $token_pos,
				);
		}

		$line_no++;
	}

	/*
	 * Emulate results returned
	 * by vipgoci_phpcs_scan_single_file().
	 */

	$results = array(
		'totals' => array(
			'errors' => $results_files[
				$temp_file_name
			]['errors'],

			'warnings' => $results_files[
				$temp_file_name
			]['warnings'],

			'fixable' => $results_files[
				$temp_file_name
			]['fixable'],
		),

		'files' => array(
			$temp_file_name =>
				$results_files[
					$temp_file_name
				]
		)
	);

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'svg_scan_single_file' );

	vipgoci_log(
		'SVG scanning of a single file finished',
		array(
			'file_issues_arr_master' => $results,
		)
	);

	return array(
		'file_issues_arr_master'	=> $results,
		'file_issues_str'		=> json_encode( $results ),
		'temp_file_name'		=> $temp_file_name,
	);
}

