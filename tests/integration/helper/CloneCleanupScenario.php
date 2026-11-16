<?php

declare( strict_types=1 );

require_once __DIR__ . '/../IncludesForTestsRepo.php';

$repo = vipgoci_unittests_setup_git_repo(
	array(
		'git-path'        => 'vipgoci-clone-fixture-git',
		'github-repo-url' => 'unused',
		'commit'          => 'master',
	)
);

$removed = false;
if ( is_string( $repo ) ) {
	$removed = vipgoci_unittests_remove_git_repo( $repo );
}

echo json_encode( array( 'result' => $repo, 'removed' => $removed ) );
