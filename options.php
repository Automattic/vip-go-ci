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

	if ( false === $options[ 'phpcs-severity-repo-options-file' ] ) {
		vipgoci_log(
			'Skipping possibly overwriting options ' .
				'using data from repository settings file ' .
				'as this is disabled via options',
			array(
				'phpcs-severity-repo-options-file'
					=> $options[ 'phpcs-severity-repo-options-file' ],
			)
		);

		return true;
	}

	vipgoci_log(
		'Reading options from repository, overwriting ' .
			'already set values if applicable',
		array(
			'repo_owner'		=> $options['repo-owner'],
			'repo_name'		=> $options['repo-name'],
			'commit'		=> $options['commit'],
			'filename'		=> $repo_options_file_name,
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
		$repo_options_file_name,
		$options['local-git-repo']
	);

	if ( false === $repo_options_file_contents ) {
		vipgoci_log(
			'No options found, nothing further to do',
			array(
				'filename' => $repo_options_file_name,		
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
					=> $repo_options_file_name,

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

	foreach(
		$options_overwritable as
			$option_overwritable_name =>
			$option_overwritable_conf
	) {
		/*
		 * Detect possible issues with
		 * the arguments given, or value not defined
		 * in the options-file.
		 */
		if (
			( ! isset(
				$repo_options_arr[
					$option_overwritable_name
				]
			) )
			||
			( ! isset(
				$option_overwritable_conf['type']
			) )
		) {
			continue;
		}


		$do_skip = false;

		if ( 'integer' === $option_overwritable_conf['type'] ) {
			if ( ! isset(
				$option_overwritable_conf['valid_values']
			) ) {
				$do_skip = true;
			}

			if ( ! in_array(
				$repo_options_arr[
					$option_overwritable_name
				],
				$option_overwritable_conf['valid_values'],
				true
			) ) {
				$do_skip = true;
			}
		}

		else {
			$do_skip = true;
		}


		if ( true === $do_skip ) {
			vipgoci_log(
				'Found invalid value for option in option-file, or invalid arguments passed internally',
				array(
					'option_overwritable_name'
						=> $option_overwritable_name,

					'option_overwritable_conf'
						=> $option_overwritable_conf,
				)
			);

			continue;
		}

			
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
	}

	vipgoci_log(
		'Set or overwrote the following options',
		$options_read
	);

	return true;
}
