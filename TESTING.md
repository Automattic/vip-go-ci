# Tests

## Introduction 

`vip-go-ci` relies on both manual and automated testing. Much of the functionality it provides is automatically tested using it's extensive unit and integration test suite. _Most_ of these tests are run automatically when code is committed and pushed to the repository, though _some_ tests need to be run manually (due to secrets, see below). The manual testing that should be performed is functional, testing the final behaviour of the software. We aim to eliminate the need for manual testing by automating them. 

## Automated testing

### Setting up test suites

First ensure that you have `phpunit` installed along with any dependencies needed (this would include `xdebug`).

Follow these steps to run the test suites.

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

Note that some tests will require a GitHub token to submit POST/PUT requests to GitHub in order to complete, and some will need access to a repo-meta API. 

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

Some functionality still needs to be tested manually.
