# API fixtures

`integration-api.json` is reviewed test data, not a response cache. The test runner never refreshes it or contacts an external API on a missing entry.

GitHub commit, diff, PR and historical comment examples come from the public `Automattic/vip-go-ci-testing` repository and its immutable commits listed in `tests/config.ini.dist`. Responses retain fields used by the clients and assertions. User lookup, organization/team membership, collaborators, event examples, WPScan and repo-meta examples use synthetic fixture identities or representative data. WordPress.org update-check responses are pinned read-only examples with exact request-body variants. Release examples are pinned to the immutable `1.4.2` tag.

Keys contain the HTTP method and full URL, including query parameters. A `__variants` entry selects a response only for the exact form payload recorded alongside it. A `__http` entry supplies a response status, headers and raw body for transport failure tests. Focused tests can instead queue exact requests with `IntegrationApiMock::expect()`; unused responses or unexpected requests fail.

Writes to the registered fixture repository's labels, comments, reviews and commit statuses update only the current isolated test's in-memory state. The real API clients and their caches still run. Keep each test's setup independent; never rely on another test's simulated write.

When a client changes, review its intended request and update the relevant fixture and assertions together. Keep meaningful negative cases (unknown resources, invalid credentials, unsupported request bodies and transport failures). Do not replace a missing response with a generic empty array or add a live fallback. Do not store credentials or private service data. Use explicit live mode separately when checking service compatibility.

Git fixture setup and scanner installation remain separate prerequisites. Fixture mode does not replace local Git operations or scanner executables. Git transport is restricted to local files during offline runs. Prepare both the local testing repository and full main-project history before starting; the submodule test uses the main checkout instead of fetching from GitHub.
