# Changelog for vip-go-ci

All notable changes to this project will be documented in this file.

## [1.0.2](https://github.com/Automattic/vip-go-ci/releases/tag/1.0.2) - 2021-05-03

### Re-release to fix issues with latest-release.php, see b057081

## [1.0.1](https://github.com/Automattic/vip-go-ci/releases/tag/1.0.1) - 2021-05-03

### Re-release to make version numbers consistent

## [1.0.0](https://github.com/Automattic/vip-go-ci/releases/tag/1.0.0) - 2021-05-03

### Fixed
- [#153](https://github.com/Automattic/vip-go-ci/pull/153): Use local git repository for sources of `git diff`, resolving problems caused by the GitHub API not returning results or skipping files for long patches (see #135).
- [#158](https://github.com/Automattic/vip-go-ci/pull/158): Fix a bug in `vipgoci_gitrepo_blame_for_file()` which can lead to PHPCS results not being posted. This will resolve #90.
- [#162](https://github.com/Automattic/vip-go-ci/pull/162): Update `testVersion` parameter in CircleCI configuration
- [#161](https://github.com/Automattic/vip-go-ci/pull/161): Update `testVersion` parameter in README.md
- [#148](https://github.com/Automattic/vip-go-ci/pull/148): Fix problem with PHPCS config files that use paths without leading `/`.
- [#150](https://github.com/Automattic/vip-go-ci/pull/150): Assign PHP linting problems a severity value.

### Updated
 - [#159](https://github.com/Automattic/vip-go-ci/pull/159): Update VIP-Coding-Standards to newer version, see #159.
 - [#143](https://github.com/Automattic/vip-go-ci/pull/143): Allow more options to be configured via repository-config file.
 - [#151](https://github.com/Automattic/vip-go-ci/pull/151): Make name of support-level field configurable.
