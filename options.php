<?php

/*
 * Read settings from a options file in the
 * repository, but only allow certain options
 * to be configured. 
 */
function vipgoci_options_read_repo_file(
	&$options,
	$repo_options_file_name,
	$options_overwritable
) {

	vipgoci_log(
		'Reading options from repository, overwriting ' .
			'already set values if applicable',
		array(
			'repo_owner'		=> $options['repo-owner'],
			'repo_name'		=> $options['repo-name'],
			'commit'		=> $options['commit'],
			'filename'		=> $filename,
			'options_overwritable'	=> $options_overwritable,
		)
	);


	/*
	 * Try to read options-file from
	 * repository, bail out of that fails.
	 */

	$repo_options_file_contents = vipgoci_gitrepo_fetch_committed_file(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$filename,
		$options['local-git-repo']
	);

	if ( false === $repo_options_file_contents ) {
		vipgoci_log(
			'No options found, nothing further to do',
			array(
				'filename'		=> $filename,		
			)
		);

		return false;
	}

	$repo_options_arr = json_decode(
		$repo_options_file_contents,
		true
	);

	if ( null === $repo_options_arr ) {
		vipgoci_log(
			'Options not parsable, nothing further to do',
			array(
				'filename'
					=> $filename,

				'repo_options_arr'
					=> $repo_options_arr,

				'repo_options_file_contents'
					=> $repo_options_file_contents,
			)
		);


		return false;
	}


	/*
	 * Actually set/overwrite values. Keep account of what we set
	 * and to what value, log it at the end.
	 */
	$options_read = array();

	foreach( $options_overwritable as $option_overwritable_name ) {
		if ( isset(
			$repo_options_arr[
				$option_overwritable_name
			]
		) ) {
			$options[
				$option_overwritable_name
			]
			=
			$options_read[
				$option_overwritable_name
			]
			=
			$repo_options_arr[
				$option_overwritable_name
			];

			// FIXME: Need to check if set values are valid.
		}		
	}

	vipgoci_log(
		'Set or overwrote the following options',
		$options_read
	);
}
