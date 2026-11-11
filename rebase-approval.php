<?php
/**
 * Carry a human approval across a provably unchanged, conflict-free rebase.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Run Git without shell expansion, inherited Git configuration, or hooks.
 *
 * @param array $arguments Git arguments.
 * @return string|null Trimmed stdout on success, null on failure or excessive output.
 */
function vipgoci_rebase_git( array $arguments ): ?string {
	if ( ! is_callable( 'proc_open' ) ) {
		return null;
	}
	$path    = getenv( 'PATH' );
	$process = proc_open(
		array_merge( array( 'git' ), $arguments ),
		array(
			0 => array( 'pipe', 'r' ),
			1 => array( 'pipe', 'w' ),
			2 => array( 'file', '/dev/null', 'w' ),
		),
		$pipes,
		null,
		array(
			'PATH'                => false !== $path && '' !== $path ? $path : '/usr/bin:/bin',
			'GIT_CONFIG_NOSYSTEM' => '1',
			'GIT_CONFIG_GLOBAL'   => '/dev/null',
			'GIT_ATTR_NOSYSTEM'   => '1',
			'GIT_TERMINAL_PROMPT' => '0',
			'LC_ALL'              => 'C',
		)
	);
	if ( ! is_resource( $process ) ) {
		return null;
	}
	fclose( $pipes[0] );
	$output = stream_get_contents( $pipes[1], 1048577 );
	fclose( $pipes[1] );
	$status = proc_close( $process );
	return ( 0 === $status && false !== $output && strlen( $output ) <= 1048576 ) ? trim( $output ) : null;
}

/**
 * Execute a callback in an isolated bare object database sharing local objects.
 *
 * No checkout, index, merge drivers, attributes, or repository configuration from
 * the scanned working tree is used. Missing objects are never fetched implicitly.
 *
 * @param string   $repository Existing local repository.
 * @param callable $callback   Receives the temporary bare repository path.
 * @return mixed Callback result, or null if the object database is unavailable.
 */
function vipgoci_rebase_with_objects( string $repository, callable $callback ): mixed {
	$common = vipgoci_rebase_git( array( '-C', $repository, 'rev-parse', '--path-format=absolute', '--git-common-dir' ) );
	if ( null === $common || str_contains( $common, "\n" ) || ! is_dir( $common . '/objects' ) ) {
		return null;
	}
	$directory = tempnam( sys_get_temp_dir(), 'vipgoci-rebase-' );
	if ( false === $directory ) {
		return null;
	}
	try {
		if ( ! unlink( $directory ) || ! mkdir( $directory, 0700 ) ) {
			return null;
		}
		if ( null === vipgoci_rebase_git( array( 'init', '--bare', '--quiet', '--template=', $directory ) ) ) {
			return null;
		}
		if ( false === file_put_contents( $directory . '/objects/info/alternates', $common . "/objects\n" ) ) {
			return null;
		}
		return $callback( $directory );
	} finally {
		if ( is_dir( $directory ) ) {
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $files as $file ) {
				if ( $file->isDir() && ! $file->isLink() ) {
					rmdir( $file->getPathname() );
				} else {
					unlink( $file->getPathname() );
				}
			}
			rmdir( $directory );
		} elseif ( is_file( $directory ) ) {
			unlink( $directory );
		}
	}
}

/**
 * Validate an object ID received from GitHub or a saved baseline.
 *
 * @param mixed $value Possible SHA-1 object ID.
 * @return bool Whether this is a full GitHub commit ID.
 */
function vipgoci_rebase_sha( mixed $value ): bool {
	return is_string( $value ) && 1 === preg_match( '/\A[0-9a-f]{40}\z/', $value );
}

/**
 * Prove that the reviewed tree mechanically integrates to the exact new tree.
 *
 * @param string $repository Local object source.
 * @param array  $baseline   Reviewed head, base tip, and original merge base.
 * @param string $base       Current trusted base tip.
 * @param string $head       Current PR head.
 * @return bool False for missing history, conflicts, unsupported Git, or any tree difference.
 */
function vipgoci_rebase_tree_matches( string $repository, array $baseline, string $base, string $head ): bool {
	foreach ( array( $baseline['head'] ?? null, $baseline['base'] ?? null, $baseline['fork'] ?? null, $base, $head ) as $sha ) {
		if ( ! vipgoci_rebase_sha( $sha ) ) {
			return false;
		}
	}
	return true === vipgoci_rebase_with_objects(
		$repository,
		static function ( string $git ) use ( $baseline, $base, $head ): bool {
			foreach ( array( array( $baseline['base'], $base ), array( $base, $head ) ) as $ancestry ) {
				if ( null === vipgoci_rebase_git( array( '-C', $git, 'merge-base', '--is-ancestor', ...$ancestry ) ) ) {
					return false;
				}
			}
			if ( $baseline['fork'] !== vipgoci_rebase_git( array( '-C', $git, 'merge-base', '--all', $baseline['base'], $baseline['head'] ) ) ) {
				return false;
			}
			$tree = vipgoci_rebase_git( array( '-C', $git, 'merge-tree', '--write-tree', '--merge-base=' . $baseline['fork'], $base, $baseline['head'] ) );
			return null !== $tree && $tree === vipgoci_rebase_git( array( '-C', $git, 'rev-parse', '--verify', $head . '^{tree}' ) );
		}
	);
}

/**
 * Read fresh paginated evidence. Partial or malformed results never authorize writes.
 *
 * @param array  $options Repository and token options.
 * @param string $path    Repository-relative REST path without a query.
 * @param bool   $paged   Whether a list is expected.
 * @return array|object|null Complete list, object, or null on failure.
 */
function vipgoci_rebase_api_read( array $options, string $path, bool $paged = true ): array|object|null {
	$url    = VIPGOCI_GITHUB_BASE_URL . '/repos/' . rawurlencode( $options['repo-owner'] ) . '/' . rawurlencode( $options['repo-name'] ) . '/' . $path;
	$result = array();
	for ( $page = 1; $page <= 100; $page++ ) {
		$raw  = vipgoci_http_api_fetch_url( $url . ( $paged ? '?per_page=100&page=' . $page : '' ), $options['token'], false );
		$data = null === $raw ? null : json_decode( $raw );
		if ( ! $paged ) {
			return is_object( $data ) && ! isset( $data->message ) ? $data : null;
		}
		if ( ! is_array( $data ) ) {
			return null;
		}
		foreach ( $data as $item ) {
			if ( ! is_object( $item ) ) {
				return null;
			}
			$result[] = $item;
		}
		if ( count( $data ) < 100 ) {
			return $result;
		}
	}
	return null;
}

/**
 * Match a still-open, non-draft PR to the exact scanned head and saved base identity.
 *
 * @param object|null $pr       Fresh PR response.
 * @param array       $options  Scanner options.
 * @param array|null  $baseline Optional baseline identity.
 * @return bool Whether the PR identity can be used.
 */
function vipgoci_rebase_pr_matches( ?object $pr, array $options, ?array $baseline = null ): bool {
	if (
		null === $pr || 'open' !== ( $pr->state ?? null ) || false !== ( $pr->draft ?? null ) ||
		$options['commit'] !== ( $pr->head->sha ?? null ) ||
		! vipgoci_rebase_sha( $pr->base->sha ?? null ) ||
		! is_int( $pr->user->id ?? null ) ||
		! is_int( $pr->base->repo->id ?? null ) || ! is_int( $pr->head->repo->id ?? null ) ||
		! is_string( $pr->base->ref ?? null ) ||
		! in_array( $pr->base->ref, $options['autoapprove-rebases-base-branches'], true )
	) {
		return false;
	}
	return null === $baseline || (
		$baseline['pr'] === ( $pr->number ?? null ) &&
		$baseline['base_ref'] === $pr->base->ref &&
		$baseline['base_repo'] === $pr->base->repo->id &&
		$baseline['head_repo'] === $pr->head->repo->id
	);
}

/**
 * Require an explicitly trusted human's latest decisive review.
 *
 * @param array  $reviews All reviews in chronological API order.
 * @param array  $events  Complete issue events.
 * @param array  $options Scanner options.
 * @param object $pr      Current PR.
 * @return array|null Eligible human reviews, or null if review history is incomplete or blocking.
 */
function vipgoci_rebase_human_reviews( array $reviews, array $events, array $options, object $pr ): ?array {
	$latest = array();
	foreach ( $reviews as $review ) {
		if ( ! is_int( $review->id ?? null ) || ! is_int( $review->user->id ?? null ) || ! is_string( $review->state ?? null ) ) {
			return null;
		}
		if ( in_array( $review->state, array( 'APPROVED', 'CHANGES_REQUESTED', 'DISMISSED' ), true ) ) {
			$latest[ $review->user->id ] = $review;
		}
	}
	foreach ( $latest as $review ) {
		if ( 'CHANGES_REQUESTED' === $review->state ) {
			return null;
		}
	}
	$eligible = array();
	foreach ( $latest as $review ) {
		if (
			'User' !== ( $review->user->type ?? null ) ||
			$review->user->id === ( $pr->user->id ?? null ) ||
			! in_array( (string) $review->user->id, $options['autoapprove-rebases-reviewer-ids'], true ) ||
			! vipgoci_rebase_sha( $review->commit_id ?? null )
		) {
			continue;
		}
		if ( 'APPROVED' === $review->state ) {
			$eligible[ $review->id ] = $review;
		} elseif ( 'DISMISSED' === $review->state ) {
			$dismissals = array_values( array_filter( $events, static fn( object $event ): bool => ( $event->dismissed_review->review_id ?? null ) === $review->id ) );
			if (
				1 === count( $dismissals ) && 'review_dismissed' === ( $dismissals[0]->event ?? null ) &&
				'approved' === ( $dismissals[0]->dismissed_review->state ?? null ) &&
				$pr->head->sha === ( $dismissals[0]->dismissed_review->dismissal_commit_id ?? null )
			) {
				$eligible[ $review->id ] = $review;
			}
		}
	}
	return $eligible;
}

/**
 * Decode a baseline recorded by this scanner's authenticated account.
 *
 * @param object $review Review to inspect.
 * @param int    $bot_id Authenticated account's stable ID.
 * @return array|null Verified baseline shape, or null.
 */
function vipgoci_rebase_baseline_read( object $review, int $bot_id ): ?array {
	$prefix = 'vip-go-ci rebase baseline v1: ';
	if (
		$bot_id !== ( $review->user->id ?? null ) || 'COMMENTED' !== ( $review->state ?? null ) ||
		! is_string( $review->body ?? null ) || ! str_starts_with( $review->body, $prefix )
	) {
		return null;
	}
	$data = json_decode( substr( $review->body, strlen( $prefix ) ), true );
	if ( ! is_array( $data ) || count( $data ) !== 9 ) {
		return null;
	}
	foreach ( array( 'head', 'base', 'fork' ) as $key ) {
		if ( ! vipgoci_rebase_sha( $data[ $key ] ?? null ) ) {
			return null;
		}
	}
	foreach ( array( 'review', 'reviewer', 'base_repo', 'head_repo', 'pr' ) as $key ) {
		if ( ! is_int( $data[ $key ] ?? null ) || $data[ $key ] < 1 ) {
			return null;
		}
	}
	return is_string( $data['base_ref'] ?? null ) && '' !== $data['base_ref'] && $data['head'] === ( $review->commit_id ?? null ) ? $data : null;
}

/**
 * Refuse carry-forward when configured scans found issues or skipped any files.
 *
 * @param array $results Unmodified scan results, before auto-approval/deduplication.
 * @param int   $pr      Pull request number.
 * @param array $options Enabled scanners and scanned commit.
 * @return bool Whether complete scan results are clear.
 */
function vipgoci_rebase_scan_clear( array $results, int $pr, array $options ): bool {
	if ( $options['commit'] !== ( $results['scan-completion']['commit'] ?? null ) ) {
		return false;
	}
	foreach ( array( 'lint', 'phpcs', 'wpscan-api', 'svg-checks' ) as $scanner ) {
		if ( true !== ( $options[ $scanner ] ?? false ) ) {
			continue;
		}
		if ( true !== ( $results['scan-completion'][ $scanner ][ $pr ] ?? null ) ) {
			return false;
		}
		if ( 'svg-checks' !== $scanner && ! isset( $results['stats'][ $scanner ][ $pr ] ) ) {
			return false;
		}
	}
	if ( ( ( $options['phpcs'] ?? false ) || ( $options['svg-checks'] ?? false ) ) &&
		true !== ( $results['rebase-clear']['phpcs'][ $pr ] ?? null ) ) {
		return false;
	}
	if ( ! isset( $results['issues'][ $pr ] ) || array() !== $results['issues'][ $pr ] || 0 !== ( $results[ VIPGOCI_SKIPPED_FILES ][ $pr ]['total'] ?? null ) ) {
		return false;
	}
	foreach ( $results['stats'] ?? array() as $stats ) {
		foreach ( $stats[ $pr ] ?? array() as $count ) {
			if ( 0 !== $count ) {
				return false;
			}
		}
	}
	return true;
}

/**
 * Submit a review and return its server-assigned identity.
 *
 * @param array $options Scanner options.
 * @param int   $number  PR number.
 * @param array $body    Review payload.
 * @return object|null Created review, or null when the response is inconclusive.
 */
function vipgoci_rebase_review_write( array $options, int $number, array $body ): ?object {
	$response = vipgoci_http_api_post_url(
		VIPGOCI_GITHUB_BASE_URL . '/repos/' . rawurlencode( $options['repo-owner'] ) . '/' . rawurlencode( $options['repo-name'] ) . '/pulls/' . $number . '/reviews',
		$body,
		$options['token']
	);
	$review   = is_string( $response ) ? json_decode( $response ) : null;
	return is_object( $review ) && is_int( $review->id ?? null ) ? $review : null;
}

/**
 * Bind a compensating dismissal to the review and original human approval.
 *
 * The marker is trusted only in a review_dismissed event by the authenticated
 * scanner account. A copied message from a different actor is not a rollback.
 *
 * @param int    $review_id Created review ID.
 * @param string $head Head approved by that review.
 * @param int    $human_review_id Original human approval ID.
 * @return string Stable dismissal message used to recognize scanner rollbacks.
 */
function vipgoci_rebase_rollback_message( int $review_id, string $head, int $human_review_id ): string {
	return 'vip-go-ci rebase rollback v1: review=' . $review_id . '; head=' . $head . '; human_review=' . $human_review_id . '. PR head, base, or review evidence changed while approving the rebase.';
}

/**
 * Parse an unambiguous UTC GitHub event timestamp.
 *
 * @param mixed $value API timestamp.
 * @return int|null Epoch seconds, or null for absent or malformed evidence.
 */
function vipgoci_rebase_timestamp( mixed $value ): ?int {
	if ( ! is_string( $value ) || 1 !== preg_match( '/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z\z/', $value ) ) {
		return null;
	}
	$time = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:s\Z', $value, new DateTimeZone( 'UTC' ) );
	return false !== $time && $time->format( 'Y-m-d\TH:i:s\Z' ) === $value ? $time->getTimestamp() : null;
}

/**
 * Do not reverse a maintainer's dismissal of an approval carried by this bot.
 *
 * @param array $reviews  Complete reviews.
 * @param array $events   Complete timeline events.
 * @param array $baseline Original human approval context.
 * @param int   $bot_id   Authenticated scanner account.
 * @return bool Whether an earlier carried approval was manually revoked.
 */
function vipgoci_rebase_approval_revoked( array $reviews, array $events, array $baseline, int $bot_id ): bool {
	$candidate_time = null;
	foreach ( $reviews as $candidate ) {
		if ( $baseline['review'] === ( $candidate->id ?? null ) ) {
			$candidate_time = vipgoci_rebase_timestamp( $candidate->submitted_at ?? null );
		}
	}
	foreach ( $reviews as $review ) {
		if (
			$bot_id !== ( $review->user->id ?? null ) || 'DISMISSED' !== ( $review->state ?? null ) ||
			1 !== preg_match( '/\AAuto-approved conflict-free rebase of human review #([1-9][0-9]*);/', $review->body ?? '', $origin )
		) {
			continue;
		}
		$dismissals = array_values( array_filter( $events, static fn( object $event ): bool => ( $event->dismissed_review->review_id ?? null ) === $review->id ) );
		if ( 1 !== count( $dismissals ) ) {
			return true;
		}
		$dismissal = $dismissals[0];
		$rollback  = 'review_dismissed' === ( $dismissal->event ?? null ) &&
			$bot_id === ( $dismissal->actor->id ?? null ) &&
			'approved' === ( $dismissal->dismissed_review->state ?? null ) &&
			vipgoci_rebase_sha( $review->commit_id ?? null ) &&
			vipgoci_rebase_rollback_message( $review->id, $review->commit_id, (int) $origin[1] ) === ( $dismissal->dismissed_review->dismissal_message ?? null );
		$automatic = 'review_dismissed' === ( $dismissal->event ?? null ) &&
			'approved' === ( $dismissal->dismissed_review->state ?? null ) &&
			vipgoci_rebase_sha( $dismissal->dismissed_review->dismissal_commit_id ?? null );
		if ( ! $rollback && ! $automatic ) {
			$dismissed_at = vipgoci_rebase_timestamp( $dismissal->created_at ?? null );
			if ( $baseline['review'] === (int) $origin[1] || null === $candidate_time || null === $dismissed_at || $candidate_time <= $dismissed_at ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Verify the submitted approval and compensate if evidence changed during POST.
 *
 * An inconclusive POST response can still have created a review. Recover its ID
 * from fresh authenticated review data rather than skipping the post-write check.
 *
 * @param array       $options   Scanner options.
 * @param array       $baseline  Original approval context.
 * @param object      $pr        PR snapshot used for the proof.
 * @param array       $reviews   Reviews immediately before submission.
 * @param array       $events    Timeline immediately before submission.
 * @param int         $bot_id    Authenticated scanner account.
 * @param array       $payload   Submitted review fields.
 * @param object|null $posted    POST response, if conclusive.
 * @return bool Whether the carried approval was confirmed against unchanged evidence.
 * @throws RuntimeException When rollback or review recovery cannot be confirmed.
 */
function vipgoci_rebase_approval_verify( array $options, array $baseline, object $pr, array $reviews, array $events, int $bot_id, array $payload, ?object $posted ): bool {
	$number        = $baseline['pr'];
	$after         = vipgoci_rebase_api_read( $options, 'pulls/' . $number, false );
	$after_reviews = vipgoci_rebase_api_read( $options, 'pulls/' . $number . '/reviews' );
	$after_events  = vipgoci_rebase_api_read( $options, 'issues/' . $number . '/timeline' );
	$old_ids       = array_column( $reviews, 'id' );
	$created       = array();
	if (
		null !== $posted && ! in_array( $posted->id, $old_ids, true )
	) {
		$created[] = $posted->id;
	}
	foreach ( $after_reviews ?? array() as $review ) {
		if (
			is_int( $review->id ?? null ) && ! in_array( $review->id, $old_ids, true ) &&
			$bot_id === ( $review->user->id ?? null ) && $payload['body'] === ( $review->body ?? null ) &&
			$payload['commit_id'] === ( $review->commit_id ?? null ) && 'APPROVED' === ( $review->state ?? null )
		) {
			$created[] = $review->id;
		}
	}
	$created   = array_unique( $created );
	$confirmed = false;
	foreach ( $after_reviews ?? array() as $review ) {
		if ( in_array( $review->id ?? null, $created, true ) && $bot_id === ( $review->user->id ?? null ) &&
			'APPROVED' === ( $review->state ?? null ) && $payload['commit_id'] === ( $review->commit_id ?? null ) &&
			$payload['body'] === ( $review->body ?? null ) ) {
			$confirmed = true;
		}
	}
	if ( null !== $after_reviews ) {
		$after_reviews = array_values( array_filter( $after_reviews, static fn( object $review ): bool => ! in_array( $review->id ?? null, $created, true ) ) );
	}
	if ( null !== $after_events ) {
		$after_events = array_values( array_filter( $after_events, static fn( object $event ): bool => 'reviewed' !== ( $event->event ?? null ) || ! in_array( $event->id ?? null, $created, true ) ) );
	}
	if (
		! $confirmed || ! vipgoci_rebase_pr_matches( $after, $options, $baseline ) || $after->base->sha !== $pr->base->sha ||
		json_encode( $after_reviews ) !== json_encode( $reviews ) || json_encode( $after_events ) !== json_encode( $events )
	) {
		$unconfirmed = array();
		foreach ( $created as $review_id ) {
			$sent   = vipgoci_github_pr_review_dismiss( $options['repo-owner'], $options['repo-name'], $number, $review_id, vipgoci_rebase_rollback_message( $review_id, $payload['commit_id'], $baseline['review'] ), $options['token'] );
			$review = vipgoci_rebase_api_read( $options, 'pulls/' . $number . '/reviews/' . $review_id, false );
			if ( $review_id !== ( $review->id ?? null ) || 'DISMISSED' !== ( $review->state ?? null ) ||
				$bot_id !== ( $review->user->id ?? null ) || $payload['commit_id'] !== ( $review->commit_id ?? null ) ) {
				$unconfirmed[] = $review_id;
				vipgoci_log(
					'Rebase approval rollback could not be confirmed',
					array(
						'pr_number'                   => $number,
						'review_id'                   => $review_id,
						'dismissal_request_succeeded' => $sent,
					),
					0,
					true
				);
			}
		}
		// An inconclusive POST may have created an approval even when a fresh list
		// has not exposed its ID. Never finish clean without reconciling that write.
		if ( ! empty( $unconfirmed ) || empty( $created ) ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Plain-text CLI error with integer GitHub IDs.
			throw new RuntimeException(
				'Rebase approval rollback is unconfirmed for PR #' . $number .
				( empty( $unconfirmed ) ? '; the submitted review ID is unknown.' : '; review IDs: ' . implode( ', ', $unconfirmed ) . '.' ) .
				' An approval may still be active. Inspect GitHub and reconcile it before retrying.'
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		return false;
	}
	return true;
}

/**
 * Record a current human approval or carry one across an exact mechanical rebase.
 *
 * Baselines are COMMENT reviews, bound to the reviewed head and authenticated by
 * their API author ID. A run on the human-approved head is required before its
 * history is rewritten. This never removes scan findings; only reviews created
 * by this run can be dismissed to compensate for a race.
 *
 * @param array $options Scanner options, configured outside the scanned repository.
 * @param array $results Original scan results.
 * @param array $prs     Pull requests implicated in the scanned commit.
 * @return array PR numbers whose rebase approvals were verified this run.
 */
function vipgoci_rebase_approval_process( array $options, array $results, array $prs ): array {
	if ( true !== ( $options['autoapprove-rebases'] ?? false ) ) {
		return array();
	}
	$bot = vipgoci_github_authenticated_user_get( $options['token'] );
	if ( ! is_int( $bot->id ?? null ) || $bot->id < 1 ) {
		vipgoci_log( 'Rebase auto-approval requires the authenticated scanner account ID' );
		return array();
	}
	$approved_prs                                = array();
	$options['autoapprove-rebases-reviewer-ids'] = array_values( array_diff( $options['autoapprove-rebases-reviewer-ids'], array( (string) $bot->id ) ) );
	foreach ( $prs as $item ) {
		$number = $item->number;
		vipgoci_log( 'Checking trusted rebase approval evidence', array( 'pr_number' => $number ) );
		if ( ! vipgoci_rebase_scan_clear( $results, $number, $options ) ) {
			vipgoci_log( 'Rebase auto-approval declined: scan results are not clear', array( 'pr_number' => $number ) );
			continue;
		}
		$pr      = vipgoci_rebase_api_read( $options, 'pulls/' . $number, false );
		$reviews = vipgoci_rebase_api_read( $options, 'pulls/' . $number . '/reviews' );
		$events  = vipgoci_rebase_api_read( $options, 'issues/' . $number . '/timeline' );
		if ( ! vipgoci_rebase_pr_matches( $pr, $options ) || $number !== ( $pr->number ?? null ) || $bot->id === $pr->user->id || null === $reviews || null === $events ) {
			vipgoci_log( 'Rebase auto-approval declined: incomplete evidence or PR identity mismatch', array( 'pr_number' => $number ) );
			continue;
		}
		// Retargeting (even away and back) invalidates the original review context.
		if ( array_filter( $events, static fn( object $event ): bool => 'base_ref_changed' === ( $event->event ?? null ) ) ) {
			vipgoci_log( 'Rebase auto-approval declined: PR has been retargeted', array( 'pr_number' => $number ) );
			continue;
		}
		$humans = vipgoci_rebase_human_reviews( $reviews, $events, $options, $pr );
		if ( null === $humans || empty( $humans ) ) {
			vipgoci_log( 'Rebase auto-approval declined: no eligible human approval', array( 'pr_number' => $number ) );
			continue;
		}
		$baselines = array();
		foreach ( $reviews as $review ) {
			$baseline = vipgoci_rebase_baseline_read( $review, $bot->id );
			if ( null !== $baseline && $number === $baseline['pr'] ) {
				$baselines[] = $baseline;
			}
		}
		foreach ( $humans as $human ) {
			$recorded = false;
			foreach ( $baselines as $baseline ) {
				if ( $baseline['review'] === $human->id ) {
					$recorded = true;
				}
			}
			if ( $options['commit'] === $human->commit_id && 'APPROVED' === $human->state && ! $recorded ) {
				$fork = vipgoci_rebase_with_objects( $options['local-git-repo'], static fn( string $git ): ?string => vipgoci_rebase_git( array( '-C', $git, 'merge-base', '--all', $pr->base->sha, $pr->head->sha ) ) );
				if ( ! vipgoci_rebase_sha( $fork ) ) {
					vipgoci_log( 'Cannot save rebase baseline: reviewed Git history is unavailable or ambiguous', array( 'pr_number' => $number ) );
					continue;
				}
				$baseline = array(
					'head'      => $human->commit_id,
					'base'      => $pr->base->sha,
					'fork'      => $fork,
					'review'    => $human->id,
					'reviewer'  => $human->user->id,
					'base_repo' => $pr->base->repo->id,
					'head_repo' => $pr->head->repo->id,
					'pr'        => $number,
					'base_ref'  => $pr->base->ref,
				);
				$fresh    = vipgoci_rebase_api_read( $options, 'pulls/' . $number, false );
				if ( vipgoci_rebase_pr_matches( $fresh, $options, $baseline ) && $pr->base->sha === $fresh->base->sha ) {
					vipgoci_rebase_review_write(
						$options,
						$number,
						array(
							'commit_id' => $options['commit'],
							'event'     => 'COMMENT',
							'body'      => 'vip-go-ci rebase baseline v1: ' . json_encode( $baseline ),
						)
					);
				}
			}
		}
		foreach ( $baselines as $baseline ) {
			$human = $humans[ $baseline['review'] ] ?? null;
			if (
				null === $human || $human->user->id !== $baseline['reviewer'] || $human->commit_id !== $baseline['head'] ||
				vipgoci_rebase_approval_revoked( $reviews, $events, $baseline, $bot->id ) ||
				$options['commit'] === $baseline['head'] || ! vipgoci_rebase_pr_matches( $pr, $options, $baseline ) ||
				! vipgoci_rebase_tree_matches( $options['local-git-repo'], $baseline, $pr->base->sha, $options['commit'] )
			) {
				continue;
			}
			$fresh_reviews = vipgoci_rebase_api_read( $options, 'pulls/' . $number . '/reviews' );
			$fresh_events  = vipgoci_rebase_api_read( $options, 'issues/' . $number . '/timeline' );
			$fresh_pr      = vipgoci_rebase_api_read( $options, 'pulls/' . $number, false );
			if ( null === $fresh_reviews || null === $fresh_events || ! vipgoci_rebase_pr_matches( $fresh_pr, $options, $baseline ) || $fresh_pr->base->sha !== $pr->base->sha ) {
				continue;
			}
			// Any change in evidence during the proof requires a new run.
			if ( json_encode( $reviews ) !== json_encode( $fresh_reviews ) || json_encode( $events ) !== json_encode( $fresh_events ) ) {
				continue;
			}
			$already_approved = array_filter( $fresh_reviews, static fn( object $review ): bool => $bot->id === ( $review->user->id ?? null ) && 'APPROVED' === ( $review->state ?? null ) && $options['commit'] === ( $review->commit_id ?? null ) );
			if ( empty( $already_approved ) ) {
				$payload = array(
					'event'     => 'APPROVE',
					'commit_id' => $options['commit'],
					'body'      => 'Auto-approved conflict-free rebase of human review #' . $human->id . '; exact integrated tree verified onto base ' . $pr->base->sha . '.',
				);
				$posted  = vipgoci_rebase_review_write( $options, $number, $payload );
				if ( vipgoci_rebase_approval_verify( $options, $baseline, $pr, $fresh_reviews, $fresh_events, $bot->id, $payload, $posted ) ) {
					$approved_prs[] = $number;
				}
			} else {
				$approved_prs[] = $number;
			}
			break;
		}
	}
	return $approved_prs;
}
