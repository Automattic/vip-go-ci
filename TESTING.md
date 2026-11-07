# Tests

## Introduction

`vip-go-ci` relies on both manual and automated testing. Much of the functionality `vip-go-ci` provides is automatically tested using it's extensive unit, integration and E2E (End-to-End) test suites. _Most_ of the tests in the test suites are run automatically when code is committed and pushed to the repository, though _some_ integration tests need to be run manually (due to secrets, see below). The manual testing that should be performed is functional, testing the final behaviour of the software.

## Automated testing

### Setting up test suites

First ensure that you have `phpunit` installed along with any add-ons needed, for example `xdebug`. Other requirements are listed in [README.md](README.md#system-requirements).

Then follow these steps to run the test suites:

1) Put the PHPUnit configuration file in place:

> mv phpunit.xml.dist phpunit.xml

2) Replace the string `PROJECT_DIR` in `phpunit.xml` with your local project directory.

For example:

> &lt;directory&gt;PROJECT_DIR/tests/integration&lt;/directory&gt;

will be:

> &lt;directory&gt;~/Projects/vip-go-ci/tests/integration&lt;/directory&gt;

3) Copy configuration file for tests and update the file as needed. Note that this step is only needed if you intend to run the integration tests.

> cp tests/config.ini.dist tests/config.ini

For fixture mode, clone `Automattic/vip-go-ci-testing` once and set `github-repo-url` to that local checkout's absolute path. Check out its `ap-file-types-test-1` branch once, then switch back to `master`, so the fixture branch is available locally. The main project checkout must include full history for the pinned submodule objects; fetch missing history during setup if using a shallow checkout.

Alter any options in the file as needed to match the setup of your system. Note that in some cases, you may have to use different PHP versions for PHPCS or the SVG scanner than `vip-go-ci` itself.

#### Test suite secrets file

Only explicit live-mode runs use this secrets file. Those tests may require a GitHub token, repo-meta API credentials or a WPScan API token. Default fixture-mode runs ignore this file.

To skip these tests, simply place an empty `config-secrets.ini` file in the `tests/` directory of `vip-go-ci` and skip the rest of this section.

To enable the testing of these, you need to set up a `tests/config-secrets.ini` file. This file should include the following fields:

```
[git-secrets]
github-token=            ; Personal access token for GitHub API.
github-skip-write-tests= ; Whether to skip tests that write data to GitHub (true or false).
team-slug=               ; Team slug to test if present, should be a string.
org-name=                ; GitHub organisation name to use in testing.

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

> VIPGOCI_TESTING_DEBUG_MODE=true tests/run-tests.sh --testsuite=unit-tests

By running this command, you will run the tests that do not depend on external calls.

#### Mocked API responses

API calls use checked-in responses by default. Run the suites through `tests/run-tests.sh`, which finds `phpunit` on `PATH`. If needed, set `VIPGOCI_PHPUNIT` to a PHPUnit executable or PHAR path and `VIPGOCI_PHP_BINARY` to the PHP interpreter.

> tests/run-tests.sh --testsuite=unit-tests
>
> tests/run-tests.sh --testsuite=integration-tests
>
> tests/run-tests.sh --testsuite=e2e-tests

The runner disables cURL execution, option setters, request-info lookup and `sleep()` in a temporary PHP INI directory inherited by PHPUnit's isolated child processes, preserving any functions already disabled in the caller's configuration. Test-only replacements capture the real clients' request options and replay `tests/fixtures/integration-api.json`. They never contact a live service or record new responses automatically. Missing fixtures fail the test. API pacing and retry delays are recorded instead of waiting; sleeps outside the API clients retain real time. Retry tests assert the requested backoff durations. The temporary INI directory is removed when the runner exits.

Integration tests continue to execute the production API clients, scanners, response parsers, caches and reporting logic. Fixture mode uses synthetic credentials and ignores `tests/config-secrets.ini`; writes update only the fixture service's per-test state. Git operations use the local fixture checkout configured in `tests/config.ini`. The offline runner allows only Git file transport; the submodule test redirects its pinned submodule to the full project checkout. Install dependencies and prepare that checkout before running tests; these setup operations can require network access.

The `mocked-api` group additionally contains focused unit tests using a strict HTTP response queue, existing mocked GitHub request-guard, comment-limit helper and Pixel API tests, and tests of the fixture transport. Run only this group with:

> tests/run-tests.sh --group=mocked-api

To run the full suites against live services and skip the tests dedicated to mocks:

> VIPGOCI_TESTING_LIVE_API=true VIPGOCI_TESTING_DEBUG_MODE=true tests/run-tests.sh --exclude-group=mocked-api

Live mode uses the normal PHP cURL functions and integration configuration, including the existing `github-skip-write-tests` control. Configure real credentials only for live runs. Set `github-skip-write-tests=true` to prevent writes during those runs; offline tests exercise the write paths against fixtures.

When adding mocked coverage:

- For focused client tests, extend `tests/common/MockedApiTestCase.php` and use the `mocked-api`, process-isolation and disabled-global-preservation attributes. Queue raw responses with `HttpApiMock::expect()`. Every expected request must be consumed, and extra requests fail immediately.
- For existing integration tests, add a reviewed response for the exact HTTP method and complete URL to `tests/fixtures/integration-api.json`. Use the fixture repository's immutable commits, retain fields the client consumes, and use synthetic identities for private-service examples. Keep assertions independent of the credential holder and mutable service data.
- Use the fixture transport's focused expectations for malformed responses, errors, headers and retries. Do not add a live fallback to make an offline test pass.

Production files do not contain test hooks. The cURL replacements are loaded only by the test bootstrap when the runner has disabled the native entry points. Plain `phpunit` remains usable for the unit suite; integration and E2E tests require the runner or an explicit live mode.

### Integration test suite

The integration tests can be run using the following command:

> VIPGOCI_TESTING_DEBUG_MODE=true tests/run-tests.sh --testsuite=integration-tests

Integration tests will execute the scanning utilities — PHPCS, SVG scanner and PHP Lint — and so paths to these, and a PHP interpreter, need to be configured. See the `tests/config.ini` file.

The default run uses API fixtures without tokens or rate-limit quota. Scanner paths and the local Git fixture repository must still be configured. To check live service compatibility, use the explicit live-mode command above.

### E2E test suite

The default E2E test uses the release response pinned in `tests/fixtures/integration-api.json` and needs its versioned Git tag locally (currently `1.4.2`). Fetch that tag before running it if necessary. In live mode it queries GitHub and needs the published release's versioned tag. The moving `latest` tag is not used.

The E2E (End-to-End) tests can be run using the following command:

> VIPGOCI_TESTING_DEBUG_MODE=true tests/run-tests.sh --testsuite=e2e-tests

### Test isolation

The test suite uses `#[RunTestsInSeparateProcesses]` and `#[PreserveGlobalState( false )]` to isolate tests. Include required files in `setUp()` so each test can define the functions it needs without leaking definitions into other tests.

Use PHPUnit attributes for test metadata. Declare coverage targets with `#[CoversFunction( 'function_name' )]` on the test class and data providers with `#[DataProvider( 'providerMethod' )]` on the test method. The configuration enables `failOnPhpunitDeprecation`, so deprecated PHPUnit features fail the run. Regenerate your local `phpunit.xml` from `phpunit.xml.dist` after updating the template.

### Integration tests and GitHub Actions

CI runs all suites through the offline runner. API fixtures replace GitHub, WordPress.org, WPScan, repo-meta and release requests; API credentials are not supplied to the jobs. The integration job prepares scanner dependencies and a local clone of the Git fixture repository before tests start. E2E checkout includes the versioned release tags.

Run the explicit live mode manually to check service compatibility. Live requests are intentionally outside the routine CI jobs.

## Manual testing

### Introduction

Manual testing is still required to ensure the final behavior of `vip-go-ci` is correct. This entails, for example, ensuring that PHPCS-issues are noted for pull requests that should have them due to problematic PHP code embedded in those pull requests.  We aim to eliminate the need for manual testing by automating them.

### Setting up

Begin by forking [this repository](https://github.com/Automattic/vip-go-ci-manual-testing). Use the forked repository to run the manual tests.

Navigate into the [tests/manual](tests/manual) directory on the command line. Then follow these steps:

1) Move the main script file:

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

3) Create the pull requests needed in the forked repository on GitHub. Use the [create-all-pull-requests.sh](tests/manual/create-all-pull-requests.sh) script to create all the pull requests needed. Note that the pull requests will need to be created by a user _different_ from the one that runs the tests in the next step. This is because GitHub does not allow [_changing_ reviews](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/reviewing-changes-in-pull-requests/reviewing-proposed-changes-in-a-pull-request#submitting-your-review) to be posted to one's own pull requests.

### Running tests

Run each test, identified by branch, found in `vipgoci-run.sh`. Ensure that the reviews, comments and labels generated are correct.
