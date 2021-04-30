# Changelog for vip-go-ci

All notable changes to this project will be documented in this file.

## [1.0.0] - 2021-05-03

### Fixed
- #153: Use local git repository for sources of `git diff`, resolving problems caused by the GitHub API not returning results or skipping files for long patches (see #135).
- #158: Fix a bug in `vipgoci_gitrepo_blame_for_file()` which can lead to PHPCS results not being posted. This will resolve #90.
- #162: Update `testVersion` parameter in CircleCI configuration
- #161: Update `testVersion` parameter in README.md

### Updated
 - #159: Update VIP-Coding-Standards to newer version, see #159.


