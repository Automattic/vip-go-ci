# Tests

## Introduction 

`vip-go-ci` relies on both manual and automated testing. Much of the functionality `vip-go-ci` provides is automatically tested using it's extensive unit and integration test suites. _Most_ of the tests in the test suites are run automatically when code is committed and pushed to the repository, though _some_ integration tests need to be run manually (due to secrets, see below). The manual testing that should be performed is functional, testing the final behaviour of the software. 

## Automated testing

### Setting up test suites

First ensure that you have `phpunit` installed along with any dependencies needed (this would include `xdebug`, `dom`, `mbstring` and `curl`).

Then follow these steps to run the test suites:

1) Run the following command:
> mv phpunit.xml.dist phpunit.xml

2) Replace the string `PROJECT_DIR` in `phpunit.xml` with your local project directory.

For example:
> <directory>PROJECT_DIR/tests/integration</directory>
will be:
> <directory>~/Projects/vip-go-ci/tests/integration</directory>

3) This step is only needed if you intend to run the integration tests. 

Start with preparing the `unittests.ini` file:

> cp unittests.ini.dist unittests.ini

Alter any options in the file as needed to match the setup of your system. Note that in some cases, you may have to use different PHP versions for PHPCS or the SVG scanner, than `vip-go-ci` itself.

#### Test suite secrets file

Note that some tests will require a GitHub token to submit POST/PUT requests to the GitHub API, some will need access to a repo-meta API and some access to the WPScan API. 

To skip these tests, simply place an empty `unittests-secrets.ini` file in the root directory of `vip-go-ci` and skip the rest of this section. 

To enable the testing of these, you need to set up a `unittests-secrets.ini` file in the root directory of `vip-go-ci`. This file should include the following fields:

```
[git-secrets]
github-token= ; Personal access token from GitHub
team-slug=    ; Team slug to test if present, is a string.
org-name=     ; GitHub organisation name to use in testing

[repo-meta-api-secrets]
repo-meta-api-base-url=         ; URL to base of meta API
repo-meta-api-user-id=          ; User ID for the meta API
repo-meta-api-access-token=     ; Access token for the meta API
repo-owner=                     ; Repository owner for the test, should be found in meta API
repo-name=                      ; Repository name for the test
support-level=                  ; Name of support level given by meta API (only used in tests)
support-level-field-name=       ; Support level field name in meta API (only used in tests)

[wpscan-api-scan]
access-token= ; Access token for WPScan API.
```

This file is not included, and needs to be configured manually.

### Unit test suite

The unit test suite can be run using the following command:

> VIPGOCI_TESTING_DEBUG_MODE=true phpunit --testsuite=unit-tests

By running this command, you will run the tests that do not depend on external calls. 

### Integration test suite

The integration tests can be run using the following command:

> VIPGOCI_TESTING_DEBUG_MODE=true phpunit --testsuite=integration-tests

Integration tests will execute the scanning utilities — PHPCS, SVG scanner and PHP Lint — and so paths to these, and a PHP interpreter, need to be configured. See the `unittests.ini` file.

By using this command, you will run the tests of the test-suite which can be run (depending on tokens and other detail), and get feedback on any errors or warnings. Note that when run, requests will be made to the GitHub API, but using anonymous calls (unless configured as shown above). It can happen that the GitHub API returns with an error indicating that the maximum limit of API requests has been reached; the solution is to wait and re-run or use authenticated calls (see above). 

### Details on tests

Note that the test suite uses the `@runTestsInSeparateProcesses` and `@preserveGlobalState` PHPUnit flags to avoid any influence of one test on another. Further, tests should include all required files in `setUp()` function to avoid the same function being defined multiple times across multiple tests during the same run. Combining the usage of `@runTestsInSeparateProcesses` and the inclusion of required files in `setUp()` means each test is independent of other tests, which enables functions to be defined for each test easily.

## Manual testing

### Introduction

Manual testing is still required to ensure the final behavior of `vip-go-ci` is correct. This entails, for example, ensuring that PHPCS-issues are noted for pull requests that should have them due to problematic PHP code embedded in those pull requests.  We aim to eliminate the need for manual testing by automating them.

### Setting up

Begin by forking [this repository](https://github.com/gudmdharalds-a8c/vip-go-ci-manual-testing). Use the forked repository to run the manual tests.

Navigate into the [scripts](scripts) directory on the command line. Then follow these steps:

1) Move the main script file.
> mv vipgoci-run.sh.dist vipgoci-run.sh

2) Add the following to a file named `vipgoci-run-secrets.sh`:

```
#!/bin/bash

# Repo settings.
export REPO_ORG=""
export REPO_NAME=""

# Access token for GitHub.
export VIPGOCI_GITHUB_TOKEN=""

# WPScan API.
export VIPGOCI_WPSCAN_API_TOKEN=""

# IRC config.
export VIPGOCI_IRC_API_BOT=""
export VIPGOCI_IRC_API_ROOM=""
export VIPGOCI_IRC_API_TOKEN=""
export VIPGOCI_IRC_API_URL=""

# Pixel API.
export VIPGOCI_PIXEL_API_GROUPPREFIX=""
export VIPGOCI_PIXEL_API_URL=""

# Generic support comments.
export VIPGOCI_POST_GENERIC_PR_SUPPORT_COMMENTS_BRANCHES=""
export VIPGOCI_POST_GENERIC_PR_SUPPORT_COMMENTS_ON_DRAFTS=""
export VIPGOCI_POST_GENERIC_PR_SUPPORT_COMMENTS_REPO_META_MATCH=""
export VIPGOCI_POST_GENERIC_PR_SUPPORT_COMMENTS_SKIP_IF_LABEL_EXISTS=""
export VIPGOCI_POST_GENERIC_PR_SUPPORT_COMMENTS_STRING=''

# Repo meta API.
export VIPGOCI_REPO_META_API_ACCESS_TOKEN=""
export VIPGOCI_REPO_META_API_BASE_URL=""
export VIPGOCI_REPO_META_API_USER_ID=""

# Reviews.
export DISMISSED_REVIEWS_EXCLUDE_REVIEWS_FROM_TEAM=""
```

Ensure to populate the relevant fields with your own values.

3) Create the pull requests needed in the forked repository on GitHub. Re-create the same pull requests as found [here](https://github.com/gudmdharalds-a8c/vip-go-ci-manual-testing/pulls), refering to the same branch names.

### Running tests

Run each test, identified by branch, found in `vipgoci-run.sh`. Ensure that the reviews, comments and labels generated are correct.

