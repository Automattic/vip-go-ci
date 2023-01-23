#!/bin/bash

#
# Shell script to create pull requests needed for testing.
#

# Include secrets needed.
. vipgoci-run-secrets.sh

#
# Specify all pull requests and their contents.
# 

PR_PHPCS_AND_LINTING_ISSUES='{"title":"phpcs-and-linting-issues branch","body":"PHPCS and linting issues","head":"phpcs-and-linting-issues","base":"main"}'
PR_PHPCS='{"title":"phpcs-issues branch","body":"PHPCS issues","head":"phpcs-issues","base":"main"}'
EXT_WITH_PHPCS_ISSUES_BRANCH='{"title":"ext-branch-with-phpcs-issues branch","body":"PHPCS issues from external branch","head":"gudmdharalds:ext-branch-with-phpcs-issues-original","base":"main"}'
PR_SVG_ISSUES_BRANCH='{"title":"svg-issues branch","body":"SVG issues","head":"svg-issues","base":"main"}'
PR_AUTO_APPROVABLE_SVG_FILE_BRANCH='{"title":"auto-approvable-svg-file branch","body":"Auto-approvable due to safe SVG file change only","head":"auto-approvable-svg-file","base":"main"}'
PR_NO_ISSUES_BRANCH='{"title":"no-issues branch","body":"No issues at all","head":"no-issues","base":"main"}'
PR_NOT_AUTO_APPROVABLE_BRANCH='{"title":"not-auto-approvable branch","body":"Approved file types, non-approved PHP file","head":"not-auto-approvable","base":"main"}'
PR_AUTO_APPROVABLE_FILE_TYPES_BRANCH='{"title":"auto-approvable-file-types branch","body":"Auto-approvable due to types of files altered","head":"auto-approvable-file-types","base":"main"}'
PR_AUTO_APPROVABLE_NON_FUNCTIONAL_CHANGES_BRANCH='{"title":"auto-approvable-non-functional-changes branch","body":"Auto-approvable due to non-functional changes","head":"auto-approvable-non-functional-changes","base":"main"}'
PR_TOO_LARGE_FILE_AND_PHPCS_ISSUES_BRANCH='{"title":"too-large-file-and-phpcs-issues branch","body":"Too large file present and files with PHPCS issues","head":"too-large-file-and-phpcs-issues","base":"main"}'
PR_TOO_LARGE_FILE_AND_NON_AUTO_APPROVABLE_FILE_BRANCH='{"title":"too-large-file-and-non-auto-approvable-file branch ","body":"Too large file present and non-auto-approvable file types altered","head":"too-large-file-and-non-auto-approvable-file","base":"main"}'
PR_VIPGOCI_OPTIONS_FILE_TEST_BRANCH='{"title":"vipgoci-options-file-test branch","body":"Test .vipgoci_options file","head":"vipgoci-options-file-test","base":"main"}'
PR_WPSCAN_API_TESTING1_BRANCH='{"title":"wpscan-api-testing1 branch","body":"A few plugins and one theme should be noted as vulnerable or obsolete","head":"wpscan-api-testing1","base":"main"}'
PR_WPSCAN_API_TESTING2_BRANCH='{"title":"wpscan-api-testing2 branch","body":"One plugin and one theme should be noted vulnerable/obsolete","head":"wpscan-api-testing2","base":"wpscan-api-testing1"}'
PR_WPSCAN_API_TESTING3_BRANCH='{"title":"wpscan-api-testing3 branch","body":"No plugins or themes should be noted vulnerable/obsolete (files deleted only)","head":"wpscan-api-testing3","base":"wpscan-api-testing2"}'
PR_WPSCAN_API_TESTING4_BRANCH='{"title":"wpscan-api-testing4 branch","body":"One plugin should be noted vulnerable/obsolete","head":"wpscan-api-testing4","base":"main"}'
PR_WPSCAN_API_TESTING5_BRANCH='{"title":"wpscan-api-testing5 branch","body":"One plugin should be noted vulnerable/obsolete","head":"wpscan-api-testing5","base":"main"}'
PR_WPSCAN_API_TESTING6_BRANCH='{"title":"wpscan-api-testing6 branch","body":"One theme, inside a plugin directory, should be noted vulnerable/obsolete","head":"wpscan-api-testing6","base":"wpscan-api-testing1"}'

#
# Create pull requests
# 
./create-pull-requests.php \
	--env-options="repo-owner=REPO_ORG,repo-name=REPO_NAME,github-token=VIPGOCI_GITHUB_TOKEN" \
	--pull-requests="[$PR_PHPCS,$PR_PHPCS_AND_LINTING_ISSUES,$EXT_WITH_PHPCS_ISSUES_BRANCH,$PR_SVG_ISSUES_BRANCH,$PR_AUTO_APPROVABLE_SVG_FILE_BRANCH,$PR_NO_ISSUES_BRANCH,$PR_NOT_AUTO_APPROVABLE_BRANCH,$PR_AUTO_APPROVABLE_FILE_TYPES_BRANCH,$PR_AUTO_APPROVABLE_NON_FUNCTIONAL_CHANGES_BRANCH,$PR_TOO_LARGE_FILE_AND_PHPCS_ISSUES_BRANCH,$PR_TOO_LARGE_FILE_AND_NON_AUTO_APPROVABLE_FILE_BRANCH,$PR_VIPGOCI_OPTIONS_FILE_TEST_BRANCH,$PR_WPSCAN_API_TESTING1_BRANCH,$PR_WPSCAN_API_TESTING2_BRANCH,$PR_WPSCAN_API_TESTING3_BRANCH,$PR_WPSCAN_API_TESTING4_BRANCH,$PR_WPSCAN_API_TESTING5_BRANCH,$PR_WPSCAN_API_TESTING6_BRANCH]"

