<?php
/**
 * No-network HTTP boundary with queued responses for approval race tests.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Return configured JSON; unknown requests fail closed.
 *
 * @param string $url Request URL.
 * @param mixed  $token Unused token.
 * @param bool   $fatal Unused failure policy.
 * @return string|null JSON response or failure.
 */
function vipgoci_http_api_fetch_url( string $url, mixed $token, bool $fatal = true ): ?string {
	$key      = parse_url( $url, PHP_URL_PATH ) . ( null === parse_url( $url, PHP_URL_QUERY ) ? '' : '?' . parse_url( $url, PHP_URL_QUERY ) );
	$response = $GLOBALS['rebase_responses'][ $key ] ?? null;
	if ( is_callable( $response ) ) {
		$response = $response();
	}
	return null === $response ? null : json_encode( $response );
}

/**
 * Record a review submission and return a server-assigned review ID.
 *
 * @param string $url Request URL.
 * @param array  $body Review payload.
 * @param mixed  $token Unused token.
 * @return string JSON response.
 */
function vipgoci_http_api_post_url( string $url, array $body, mixed $token ): string {
	$GLOBALS['rebase_posts'][] = array(
		'url'  => $url,
		'body' => $body,
	);
	$review                    = array(
		'id'        => 900,
		'state'     => 'APPROVE' === $body['event'] ? 'APPROVED' : 'COMMENTED',
		'commit_id' => $body['commit_id'],
		'body'      => $body['body'],
		'user'      => array( 'id' => 999 ),
	);
	$path                      = parse_url( $url, PHP_URL_PATH );
	$timeline                  = str_replace( '/pulls/', '/issues/', substr( $path, 0, -strlen( '/reviews' ) ) ) . '/timeline?per_page=100&page=1';
	foreach ( array(
		$path . '?per_page=100&page=1' => $review,
		$timeline                      => array_merge( $review, array( 'event' => 'reviewed' ) ),
	) as $key => $value ) {
		if ( isset( $GLOBALS['rebase_responses'][ $key ] ) && is_array( $GLOBALS['rebase_responses'][ $key ] ) ) {
			$GLOBALS['rebase_responses'][ $key ][] = $value;
		}
	}
	return $GLOBALS['rebase_post_response'] ?? json_encode( $review );
}

/**
 * Record compensating dismissals without changing GitHub.
 *
 * @param string $url Request URL.
 * @param array  $body Dismissal payload.
 * @param string $token Unused token.
 * @return int Success code.
 */
function vipgoci_http_api_put_url( string $url, array $body, string $token ): int {
	$GLOBALS['rebase_puts'][] = array(
		'url'  => $url,
		'body' => $body,
	);
	return 0;
}

/**
 * Return a non-qualifying PHP file for the same-head file-policy rerun fixture.
 *
 * @param array  $options Unused scanner options.
 * @param string $commit Unused scanned commit.
 * @param array  $skipped Unused skipped files.
 * @param mixed  ...$flags Unused discovery options.
 * @return array Changed files for the verified PR.
 */
function vipgoci_github_files_affected_by_commit( array $options, string $commit, array &$skipped, mixed ...$flags ): array {
	++$GLOBALS['rebase_file_policy_calls'];
	return array( 7 => array( 'file.php' ) );
}
