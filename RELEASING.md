# Releasing a new version of vip-go-ci

Releasing a new version of `vip-go-ci` entails a bit of preparation. Follow the steps in each section below to release a new version.

## Creating a new version of vip-go-ci

A few steps need to be completed to define a new version of `vip-go-ci` and have everything ready for a new release:

 * Select a version number. Version numbers follow this pattern: `X.Y.Z`.
 * Commit the new version number to [defines.php](defines.php) into a branch named `add-changelog-X-Y-Z` where `X`, `Y` and `Z` form the version number.
 * Open up a [new pull request](https://github.com/Automattic/vip-go-ci/compare) in the code repository. This pull request should be used to update the version number in `defines.php` and to append to the [changelog](CHANGELOG.md).
   * Use the TODO list template that is automatically provided in the pull request (defined [here](https://github.com/Automattic/vip-go-ci/blob/trunk/.github/PULL_REQUEST_TEMPLATE)). Use the section of the template intended for use as a changelog pull request. An example pull request can be found [here](https://github.com/Automattic/vip-go-ci/pull/312/).
   * Assign a milestone to the newly created pull request that matches the version number selected.
   * Use the new pull request to add items to the [CHANGELOG.md](https://github.com/Automattic/vip-go-ci/blob/trunk/CHANGELOG.md) file.
   * Avoid altering any functionality in this pull request.
 * Assign the newly formed milestone to any pull requests intended to be part of the release.

## Testing the new version

Follow these steps to test the new version before a release:

 * Ensure all pull requests that update the code have been merged. Do _not_ merge the changelog/version number pull request yet.
 * Ensure that all TODO items in the pull request created for changelog and version number have been completed.
 * Some of the TODO items involve running the individual test suites included. The test suites should be run against the main branch of the repository when all pull requests have been merged (except for the changelog version/number pull request). See more about testing [here](TESTS.md).
 * Manually test `vip-go-ci` against common code patterns, using this [script](). Note that a few environmental variables have to be adjusted before using the script. Note that the script has many different branches to select from, run the script against each branch at least once. You can fork [this repository](https://github.com/gudmdharalds-a8c/vip-go-ci-manual-testing) and use it to test; note that you will have to re-create the pull requests manually. The repository and the script work in tandem.
 * If any problems are found in the steps above, fix those before continuing.
 * When all pull requests with new or modified code have been merged, ensure that the pull request that modifies the version number and changelog is merged. Ensure all tests pass when this pull request has been merged.

By now, everything is ready for the release itself.

## Actually releasing a new version

Follow these steps to release a new version of `vip-go-ci`:

 * Ensure it is not Friday. A new version of `vip-go-ci` should not be released just before the weekend.
 * Ensure that the versioning and testing stages above have been completed.
 * Ensure final testing is complete.
 * Visit the [releases section of the vip-go-ci repository](https://github.com/Automattic/vip-go-ci/releases).
 * Press _Draft a new release_, enter a version number in the _Tag version_ field and ensure that the _Release title_ field contains the same value. Then press _Publish release_.
 * Update the [latest tag](https://github.com/Automattic/vip-go-ci/releases/tag/latest) so that it's commit-ID matches the one of the latest released version. This tag is used by [tools-init.sh](https://github.com/Automattic/vip-go-ci/blob/trunk/tools-init.sh) and referred to in the [README.md](https://github.com/Automattic/vip-go-ci/blob/trunk/README.md). Replace the latest tag by using the git command line: `git pull ; git tag -d latest ; git tag latest X.Y.Z &amp;&amp; git push --force origin latest`. Then ensure that the latest tag refers to the same commit-ID as the release itself, [here](https://github.com/Automattic/vip-go-ci/tags).
 * The new version will be automatically deployed by the `tools-init.sh` script where it is used.
 * _Ensure internal steps are followed for this section._

_When new release is ready, ensure to follow all internal post-release steps._

## Rolling back a release

In some cases, it may be necessary to roll back a release. Follow this list to do so:

 * Simply issue a _new release_ that points to an older commit-ID which an older, stable release is based on.
  * For example, if problems are found in the (imaginary) `2.0.7` version, we would release version `2.0.8` which would point to `490c36892a0309988386e6c8d3fbbdb05bcf0244` (which would also be tagged as `2.0.6`). In the end, two releases would point to the same commit-ID, and hence would be identical.
 * Removing the latest release on GitHub will _not_ ensure a revert to an older version automatically; a new version should be released.

