<?php

/*
 * Fetch meta-data for repository from
 * repo meta API, cache the results in memory.
 */
function vipgoci_repo_meta_api_data_fetch(
) {
	return array();
}

/*
 * Attach support level label to
 * Pull-Request, if configured to 
 * do so. Will fetch information
 * about support-level from an API.
 */
function vipgoci_support_level_label_set(
	$options
) {

	if ( true !== $options['set-support-level-label'] ) {
		vipgoci_log(
			'Not attaching support label to Pull-Requests ' .
				'implicated by commit, as not configured ' .
				'to do so',
			array(
				'set_support_level_label'
					=> $options['set-support-level-label']
			)
		);

		return;
	}

	vipgoci_log(
		'Attaching support-level label to Pull-Requests implicated by commit',
		array(
			'repo_owner'		=> $options['repo-owner'],
			'repo_name'		=> $options['repo-name'],
			'commit'		=> $options['commit'],
		)
	);

	/*
	 * Get information from API about the
	 * repository, including support level.
	 */
	$repo_meta_data = vipgoci_repo_meta_api_data_fetch(
	);

	/*
	 * Construct support-level label
	 * from information found in API,
	 * if available.
	 */
	$support_label_prefix = '[Support Level]';
	$support_label_from_api = '';

	if (
		( ! empty(
			$repo_meta_data['data']
		) )
		&&
		( ! empty(
			$repo_meta_data['data'][0]['support_tier']
		) )
	) {
		/*
		 * Construct the label itself
		 * from prefix and support level
		 * found in API.
		 */
		$support_label_from_api =
			$support_label_prefix .
			' ' .
			ucfirst(
				$repo_meta_data['data'][0]['support_tier']
			);
	}

	/*
	 * No support label found in API, so
	 * do not do anything.
	 */
	if ( empty( $support_label_from_api ) ) {
		vipgoci_log(
			'Found no valid support level in API, so not ' .
				'attaching any label (nor removing)',
			array(
				'repo_owner'		=> $options['repo-owner'],
				'repo_name'		=> $options['repo-name'],
				'support_tier'		=> (
					isset( $repo_meta_data['data'][0]['support_tier'] ) ?
					$repo_meta_data['data'][0]['support_tier'] :
					''
				),
			)
		);

		return;
	}

	else {
		vipgoci_log(
			'Found valid support level in API, making alterations as needed',
			array(
				'repo_owner'			=> $options['repo-owner'],
				'repo_name'			=> $options['repo-name'],
				'support_tier'			=> (
					isset( $repo_meta_data['data'][0]['support_tier'] ) ?
					$repo_meta_data['data'][0]['support_tier'] :
					''
				),
				'support_label_from_api'	=> $support_label_from_api,
			)
		);
	}

	/*
	 * Get Pull-Requests associated with the
	 * commit and repository.
	 */
	$prs_implicated = vipgoci_github_prs_implicated(
		$options['repo-owner'],
		$options['repo-name'],
		$options['commit'],
		$options['token'],
		$options['branches-ignore']
	);

	/*
	 * Loop through each Pull-Request,
	 * remove any invalid support levels
	 * and add a correct one.
	 *
	 * If everything is correct, will not
	 * make any alterations.
	 */
	foreach ( $prs_implicated as $pr_item ) {
		$pr_correct_support_label_found = false;

		/*
		 * Get labels for PR.
		 */
		$pr_item_labels =
			vipgoci_github_labels_get(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number
			);


		/*
		 * If no found, substitute boolean for empty array.
		 */
		if ( false === $pr_item_labels ) {
			$pr_item_labels = array();
		}

		/*
		 * Loop through each label found for
		 * Pull-Request, figure out if is support
		 * label, remove if not the same as is supposed
		 * to be set.
		 */
		foreach(
			$pr_item_labels as $pr_item_label
		) {
			if ( strpos(
				$pr_item_label->name,
				$support_label_prefix . ' '
			) !== 0 ) {
				/*
				 * Not support level
				 * label, skip.
				 */
				continue;
			}

			if ( $pr_item_label->name === $support_label_from_api ) {
				$pr_correct_support_label_found = true;

				/*
				 * We found correct support label
				 * associated, note that for later
				 * use.
				 */
				continue;
			}

			/*
			 * All conditions met; is support level
			 * label, but incorrect one, so remove label.
			 */
			vipgoci_github_label_remove_from_pr(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number,
				$pr_item_label->name,
				false
			);
		}

		/*
		 * Add support label if found in API but
		 * not if correct one is associated on
		 * GitHub already.
		 */
		if ( $pr_correct_support_label_found === true ) {
			vipgoci_log(
				'Correct support label already attached to Pull-Request, skipping',
				array(
					'repo_owner'			=> $options['repo-owner'],
					'repo_name'			=> $options['repo-name'],
					'support_label_from_api'	=> $support_label_from_api,
				)
			);
		}

		/*
		 * A support label was found in API and
		 * a correct one was not associated on
		 * GitHub already, so add one.
		 */
		else if ( $pr_correct_support_label_found === false ) {
			vipgoci_github_label_add_to_pr(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number,
				$support_label_from_api,
				false
			);
		}
	}
}
