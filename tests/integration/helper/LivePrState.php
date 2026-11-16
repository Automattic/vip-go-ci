<?php
/**
 * Restore the GitHub state owned by one integration test invocation.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use RuntimeException;
use Throwable;

// Reads must return transport failures: exiting PHP would bypass teardown's finally.
final class LivePrState {
	private array $labels_before = array();
	private array $reviews_before = array();
	private ?int $user_id = null;

	public function __construct(
		private readonly string $owner,
		private readonly string $repo,
		private readonly string $token,
		private readonly int $pr_number,
		private readonly array $tracked_labels,
		private readonly ?string $review_marker = null,
		private readonly ?string $commit = null
	) {
		$this->labels_before = $this->labels_get();
		if ( null !== $review_marker ) {
			$user = json_decode( vipgoci_http_api_fetch_url( VIPGOCI_GITHUB_BASE_URL . '/user', $this->token, false ) ?? '' );
			if ( ! is_object( $user ) || ! isset( $user->id, $user->login ) || ! is_int( $user->id ) || $user->id <= 0 || ! is_string( $user->login ) || '' === $user->login ) {
				throw new RuntimeException( 'Could not identify token holder for test PR ' . $this->owner . '/' . $this->repo . '#' . $this->pr_number );
			}
			$this->user_id = (int) $user->id;
			$this->reviews_before = $this->reviews_get();
		}
	}

	public function has_own_approval(): bool {
		foreach ( $this->reviews_before as $review ) {
			if ( 'APPROVED' === $review->state ) {
				return true;
			}
		}
		return false;
	}

	public function had_label( string $label ): bool {
		return in_array( $label, $this->labels_before, true );
	}

	public function restore(): void {
		$errors = array();
		try {
			$current = $this->labels_get();
			foreach ( $this->tracked_labels as $label ) {
				$before = in_array( $label, $this->labels_before, true );
				$after  = in_array( $label, $current, true );
				if ( $before === $after ) {
					continue;
				}
				try {
					if ( $before ) {
						vipgoci_github_label_add_to_pr( $this->owner, $this->repo, $this->token, $this->pr_number, $label );
					} else {
						vipgoci_github_pr_label_remove( $this->owner, $this->repo, $this->token, $this->pr_number, $label );
					}
				} catch ( Throwable $error ) {
					$errors[] = 'label ' . $label . ': ' . $error->getMessage();
				}
			}
			$current = $this->labels_get();
			foreach ( $this->tracked_labels as $label ) {
				if ( in_array( $label, $this->labels_before, true ) !== in_array( $label, $current, true ) ) {
					$errors[] = 'label ' . $label . ' differs from baseline';
				}
			}
		} catch ( Throwable $error ) {
			$errors[] = 'label readback: ' . $error->getMessage();
		}

		if ( null !== $this->review_marker ) {
			try {
				$before_ids = array_map( static fn( object $review ): int => (int) $review->id, $this->reviews_before );
				foreach ( $this->reviews_get() as $review ) {
					if ( in_array( (int) $review->id, $before_ids, true ) || 'APPROVED' !== $review->state ) {
						continue;
					}
					if (
						! str_contains( (string) ( $review->body ?? '' ), $this->review_marker ) ||
						$this->commit !== ( $review->commit_id ?? null )
					) {
						$errors[] = 'new approval ' . $review->id . ' lacks this invocation marker or commit; left untouched';
						continue;
					}
					try {
						vipgoci_github_pr_review_dismiss(
							$this->owner, $this->repo, $this->pr_number, (int) $review->id,
							'Dismissing approval created by integration test ' . $this->review_marker,
							$this->token
						);
					} catch ( Throwable $error ) {
						$errors[] = 'review ' . $review->id . ': ' . $error->getMessage();
					}
				}
				$remaining = array();
				$final_reviews = $this->reviews_get();
				foreach ( $final_reviews as $review ) {
					if ( 'APPROVED' === $review->state && ! in_array( (int) $review->id, $before_ids, true ) ) {
						$remaining[] = (int) $review->id;
					}
				}
				foreach ( $this->reviews_before as $before ) {
					$matching = array_values( array_filter( $final_reviews, static fn( object $review ): bool => (int) $review->id === (int) $before->id ) );
					if ( 1 !== count( $matching ) || $matching[0]->state !== $before->state ) {
						$errors[] = 'preexisting review ' . $before->id . ' differs from baseline';
					}
				}
				if ( array() !== $remaining ) {
					$errors[] = 'new approvals remain: ' . implode( ', ', $remaining );
				}
			} catch ( Throwable $error ) {
				$errors[] = 'review readback: ' . $error->getMessage();
			}
		}

		if ( array() !== $errors ) {
			throw new RuntimeException(
				'Could not restore test PR ' . $this->owner . '/' . $this->repo . '#' . $this->pr_number . ': ' . implode( '; ', $errors )
			);
		}
	}

	private function labels_get(): array {
		$names = array();
		$base_url = VIPGOCI_GITHUB_BASE_URL . '/repos/' . rawurlencode( $this->owner ) . '/' . rawurlencode( $this->repo ) . '/issues/' . $this->pr_number . '/labels';
		$page = 1;
		do {
			$url = $base_url . ( 1 === $page ? '' : '?page=' . $page );
			$labels = json_decode( vipgoci_http_api_fetch_url( $url, $this->token, false ) ?? '' );
			if ( ! is_array( $labels ) ) {
				throw new RuntimeException( 'Invalid labels response for PR #' . $this->pr_number . ' page ' . $page );
			}
			foreach ( $labels as $label ) {
				if ( ! is_object( $label ) || ! isset( $label->name ) || ! is_string( $label->name ) ) {
					throw new RuntimeException( 'Malformed label in response for PR #' . $this->pr_number . ' page ' . $page );
				}
				$names[] = $label->name;
			}
			$page++;
		} while ( count( $labels ) >= 30 );
		return $names;
	}

	private function reviews_get(): array {
		$own = array();
		$page = 1;
		do {
			$url = VIPGOCI_GITHUB_BASE_URL . '/repos/' . rawurlencode( $this->owner ) . '/' . rawurlencode( $this->repo ) . '/pulls/' . $this->pr_number . '/reviews?per_page=100&page=' . $page;
			$reviews = json_decode( vipgoci_http_api_fetch_url( $url, $this->token, false ) ?? '' );
			if ( ! is_array( $reviews ) ) {
				throw new RuntimeException( 'Invalid reviews response for PR #' . $this->pr_number . ' page ' . $page );
			}
			foreach ( $reviews as $review ) {
				if ( ! is_object( $review ) || ! isset( $review->id, $review->state, $review->user->id ) || ! is_int( $review->id ) || $review->id <= 0 || ! is_string( $review->state ) || ! in_array( $review->state, array( 'APPROVED', 'CHANGES_REQUESTED', 'COMMENTED', 'DISMISSED', 'PENDING' ), true ) || ! is_int( $review->user->id ) || $review->user->id <= 0 ) {
					throw new RuntimeException( 'Malformed review in response for PR #' . $this->pr_number . ' page ' . $page );
				}
				if ( (int) $review->user->id === $this->user_id ) {
					$own[] = $review;
				}
			}
			$page++;
		} while ( count( $reviews ) >= 100 );
		return $own;
	}
}
