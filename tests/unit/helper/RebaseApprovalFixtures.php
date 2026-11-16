<?php
/**
 * Local Git histories for rebase approval tests.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Create an approved change, unrelated upstream update, and equivalent rebased head.
 *
 * @return array Local repository and pinned object IDs.
 */
function vipgoci_unittests_rebase_repository(): array {
	$directory = tempnam( sys_get_temp_dir(), 'rebase-test-' );
	unlink( $directory );
	mkdir( $directory, 0700 );
	vipgoci_rebase_git( array( 'init', '--quiet', '--template=', $directory ) );
	file_put_contents( $directory . '/file.php', "<?php\necho 'before';\n" );
	$base = vipgoci_unittests_rebase_commit( $directory );
	file_put_contents( $directory . '/file.php', "<?php\necho 'reviewed';\n" );
	$reviewed = vipgoci_unittests_rebase_commit( $directory );
	vipgoci_rebase_git( array( '-C', $directory, 'checkout', '--quiet', '--detach', $base ) );
	file_put_contents( $directory . '/upstream.txt', 'trusted update' );
	$upstream = vipgoci_unittests_rebase_commit( $directory );
	file_put_contents( $directory . '/file.php', "<?php\necho 'reviewed';\n" );
	$head = vipgoci_unittests_rebase_commit( $directory );
	return array(
		'directory' => $directory,
		'base'      => $base,
		'reviewed'  => $reviewed,
		'upstream'  => $upstream,
		'head'      => $head,
	);
}

/**
 * Commit staged fixture content with a deterministic test identity.
 *
 * @param string $directory Fixture repository.
 * @return string New commit ID.
 */
function vipgoci_unittests_rebase_commit( string $directory ): string {
	vipgoci_rebase_git( array( '-C', $directory, 'add', '--all' ) );
	vipgoci_rebase_git( array( '-C', $directory, '-c', 'user.name=Test', '-c', 'user.email=test@example.invalid', 'commit', '--quiet', '-m', 'fixture' ) );
	return vipgoci_rebase_git( array( '-C', $directory, 'rev-parse', 'HEAD' ) );
}

/**
 * Remove only the temporary repository created by the test.
 *
 * @param string $directory Temporary fixture directory.
 * @return void
 */
function vipgoci_unittests_rebase_remove( string $directory ): void {
	$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $files as $file ) {
		if ( $file->isDir() && ! $file->isLink() ) {
			rmdir( $file->getPathname() );
		} else {
			unlink( $file->getPathname() );
		}
	}
	rmdir( $directory );
}
