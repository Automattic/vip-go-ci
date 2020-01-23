# vip-go-ci

Continuous integration for VIP Go repositories.

`vip-go-ci` is a PHP-program that can be called for latest commits pushed to Pull-Requests on GitHub, looking for problems in the code using PHP linting, [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer/), and a [SVG scanner](https://github.com/Automattic/vip-go-svg-sanitizer) -- and then posting back to GitHub comments and reviews, detailing the issues found. `vip-go-ci` can also automatically approve Pull-Requests that contain already-approved files (registered in a special database) and/or contain file-types that are approvable by default.

`vip-go-ci` is to be called from the commandline, using several arguments specifying the repository and commit-ID to scan, and various other options. During execution, `vip-go-ci` will provide a detailed log of its actions and what it encounters. The program expects a fully-functional git-repository to be stored locally on the computer running it, were from it can extract various information.

It has different behaviours for different scanning-methods. For PHP linting, it will loop through every file existing in the code-base, and post a generic Pull-Request comment for any issues it finds with the PHP-code. In case of PHPCS scanning, however, it will scan only the files affected by the Pull-Request using PHPCS, and for any issues outputted by PHPCS, post a comment on the commit with the issue, in the form of a 'GitHub Review' (this includes inline comments and a review-message). SVG scanning behaves similar to PHPCS scanning. What scanning is performed can be customised on the command-line.

This program comes with a small utility, `tools-init.sh`, that will install PHPCS and related tools in your home-directory upon execution. This utility will check if the tools required are installed, and if not, install them, or if they are, check if they are of the latest version, and upgrade them as needed. It is highly recommended to run this utility on a regular basis.

## Setting up

### On the console, standalone

`vip-go-ci` can be run standalone on the console. This is mainly useful for debugging purposes and to understand if everything is correctly configured, but for production purposes it should ideally be run via some kind of build management software (for instance TeamCity or GitHub Actions). To run `vip-go-ci` on the console, a few tools are required. The `tools-init.sh` script that is included will install the tools needed.

After the tools have been installed, `vip-go-ci.php` can be run on your local console to scan a particular commit in a particular repository:

> ./vip-go-ci.php --repo-owner=`repo-owner` --repo-name=`repo-name` --commit=`commit-ID` --token=`GitHub-Access-Token` --local-git-repo=`Local-Git-Repo` --phpcs-path=`phpcs-path` --phpcs=true --lint=true --autoapprove=true --autoapprove-filetypes=`File-Types`

-- where `repo-owner` is the GitHub repository-owner, `repo-name` is the name of the repository, `commit-ID` is the SHA-hash identifying the commit, `Local-Git-Repo` is a path to the git-repository used to scan, `GitHub-Access-Token` is a access-token created on GitHub that allows reading and commenting on the repository in question, `path-to-phpcs` is a full path to PHPCS, and `File-Types` refers to a list of file-types to be approved (such as `css,txt,pdf`). 

The output from `vip-go-ci` you will get by running the command above should be something like this:

```
[ 2018-04-16T14:10:04+00:00 -- 0 ]  Initializing...; []
[ 2018-04-16T14:10:04+00:00 -- 0 ]  Trying to get information about the user the GitHub-token belongs to; []
[ 2018-04-16T14:10:05+00:00 -- 0 ]  Starting up...; {
    "options": {
        "repo-owner": "mygithubuser",
        "repo-name": "testing123",
        "commit": "f978c17f8f648e5ce10aa16694c74a5544b1670e",
        "local-git-repo": "\/tmp\/git-testing123",
        "phpcs": true,
        "lint": true,
        "phpcs-path": "\/home\/myuser\/vip-go-ci-tools\/phpcs\/bin\/phpcs",
        "phpcs-standard": "WordPress-VIP-Go",
        "phpcs-severity": 5,
        "branches-ignore": [],
        "autoapprove": true,
        "autoapprove-filetypes": [ "css", "txt", "pdf ],
        "php-path": "php",
        "debug-level": 0,
        "dry-run": false
    }
}
[ 2018-04-16T14:10:05+00:00 -- 0 ]  Fetching all open Pull-Requests from GitHub; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "f978c17f8f648e5ce10aa16694c74a5544b1670e",
    "branches_ignore": []
}
[ 2018-04-16T14:10:14+00:00 -- 0 ]  Fetching information about all commits made to Pull-Request #17 from GitHub; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "pr_number": 17
}
[ 2018-04-16T14:10:48+00:00 -- 0 ]  About to clean up generic PR comments on Github; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "f978c17f8f648e5ce10aa16694c74a5544b1670e",
    "branches_ignore": [],
    "dry_run": false
}
[ 2018-04-16T14:10:48+00:00 -- 0 ]  About to lint PHP-files; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "f978c17f8f648e5ce10aa16694c74a5544b1670e"
}
[ 2018-04-16T14:10:50+00:00 -- 0 ]  About to PHP-lint file; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "f978c17f8f648e5ce10aa16694c74a5544b1670e",
    "filename": "bla-1.php",
    "temp_file_name": "\/tmp\/lint-scan-jniXTN"
}
[ 2018-04-16T14:10:50+00:00 -- 0 ]  Fetching file-contents from local Git repository; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "f978c17f8f648e5ce10aa16694c74a5544b1670e",
    "filename": "bla-2.php",
    "local_git_repo": "\/tmp\/git-testing123"
}
[ 2018-04-16T14:10:51+00:00 -- 0 ]  About to PHPCS-scan all files affected by any of the Pull-Requests; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "f978c17f8f648e5ce10aa16694c74a5544b1670e"
}
[ 2018-04-16T14:10:51+00:00 -- 0 ]  Fetching file-contents from local Git repository; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "f978c17f8f648e5ce10aa16694c74a5544b1670e",
    "filename": "bla-2.php",
    "local_git_repo": "\/tmp\/git-testing123"
}
[ 2018-04-16T14:10:51+00:00 -- 0 ]  About to PHPCS-scan file; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "f978c17f8f648e5ce10aa16694c74a5544b1670e",
    "filename": "bla-2.php",
    "temp_file_name": "\/tmp\/phpcs-scan-U3DbUE.php"
}
[ 2018-04-16T14:10:52+00:00 -- 0 ]  About to submit generic PR comment to GitHub about issues; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "f978c17f8f648e5ce10aa16694c74a5544b1670e",
    "results": {
        "issues": {
            "12": []
        },
        "stats": {
            ...
        }
    },
    "dry_run": false
}
[ 2018-04-16T14:10:52+00:00 -- 0 ]  About to submit comment(s) to GitHub about issue(s); {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "f978c17f8f648e5ce10aa16694c74a5544b1670e",
    "results": {
        "issues": {
            "12": []
        },
        "stats": {
		...
        }
    },
    "dry_run": false
}
[ 2018-04-16T14:10:52+00:00 -- 0 ]  Shutting down; {
    "run_time_seconds": 48,
    "run_time_measurements": {
        "git_cli": 0.0699622631073,
        "github_forced_wait": 11.001686811447,
        "github_api": 30.39110994339,
        "lint_scan_commit": 1.7449381351471,
        "git_repo_scandir": 9.8228454589844e-5,
        "lint_scan_single_file": 0.22931528091431,
        "git_repo_fetch_file": 0.00043511390686035,
        "save_temp_file": 0.00095939636230469,
        "php_lint_cli": 0.20436358451843,
        "phpcs_scan_commit": 2.2697520256042,
        "phpcs_scan_single_file": 0.33324813842773,
        "phpcs_cli": 0.32072472572327,
        "git_repo_blame_for_file": 0.020441055297852
    },
    "results": {
        "issues": {
            "12": []
        },
        "stats": {
            ...
	}
    }
}
```

### Configuring TeamCity runner

You can set up `vip-go-ci` with TeamCity, so that when a commit gets pushed to GitHub, `vip-go-ci.php` will run and scan the commit. TeamCity is not required, any other similar build management software can be used. 

This flowchart shows how `vip-go-ci` interacts with TeamCity, git, GitHub, and the utilities it uses:

![Flowchart](https://raw.githubusercontent.com/Automattic/vip-go-ci/master/docs/vipgoci-flow.png)

To get `vip-go-ci` working, follow these steps:

* Create a project, and link it to the GitHub repository you wish to scan
* Create a build-runner by clicking on `Create build configuration` on the project
* Define a build-feature, by clicking on `Add Build Feature` (located in `Build Features`, found in the project-settings). Define the type of the build-feature as `Commit status publisher`, `VCS Root` as `All attached VCS Roots`, and `Publisher` as `GitHub`.
* Click on `Version Control Settings` (in the project-settings), make sure to do the following:
  - Checkout directory as `Custom path`, and path as something unique and unreadable from other users (local-directory for the build-runner user would be optimal).
  - Click on `Clean all files in the checkout directory before the build`.
* Define parameters for the build. In the project-settings, click on`Parameters`, then click 'Add Parameter' and follow the on-screen instructions. The parameters that need to be added are `REPO_ORG`, `REPO_NAME`, and `REPO_TOKEN` and with values appropriate with for the repository which is to be scanned.

* Make sure the build-runner is of the `Command Line` type, that `If all previous steps finished successfully` is chosen, and that `Custom Script` is chosen for the run `Run` field.
* Add a shell-script into the `Custom Script` field, the script should look something like the following:

```
#
# If vip-go-ci-tools does not exist, get it in place.
# by fetching and running tools-init. If it does exist,
# run tools-init.sh anyway to check for updates.
#

if [ -d ~/vip-go-ci-tools ] ; then
	bash ~/vip-go-ci-tools/vip-go-ci/tools-init.sh
else
	wget https://raw.githubusercontent.com/Automattic/vip-go-ci/master/tools-init.sh -O tools-init.sh && \
	bash tools-init.sh && \
	rm -f tools-init.sh
fi


#
# Make sure to disable PHPCS-scanning by default
#

PHPCS_ENABLED=${PHPCS_ENABLED:-false}

#
# Actually run vip-go-ci
#

php ~/vip-go-ci-tools/vip-go-ci/vip-go-ci.php --repo-owner="$REPO_ORG" --repo-name="$REPO_NAME" --commit="$BUILD_VCS_NUMBER"  --token="$REPO_TOKEN" --local-git-repo="%system.teamcity.build.checkoutDir%" --phpcs="$PHPCS_ENABLED" --lint="$LINTING_ENABLED"  --phpcs-path="$HOME/vip-go-ci-tools/phpcs/bin/phpcs"
```

Note that the script has built-in commands to install all the utilities `vip-go-ci` relies on (via `tools-init.sh`), so that they will be configured automatically, and updated automatically as well.

The parameters should be pretty-much self-explanatory. Note that --commit should be left exactly as shown above, as `$BUILD_VCS_NUMBER` is populated by TeamCity. Other variables, `$REPO_ORG`, `$REPO_NAME` and `$REPO_TOKEN` are populated by TeamCity on run-time according to your settings (see above).

That is it. Now TeamCity should run `vip-go-ci.php` for every incoming commit to any Pull-Request associated with the repository.


### Starting a local instance of TeamCity

You can start a local instance of TeamCity in Docker if you like.

```
docker-compose up -d
open http://localhost:8111
```

To start with multiple agents (for example, three):

```
docker-compose up -d --scale agent=3
```

Alternatively, if you do not wish to run TeamCity in a Docker-instance, you can download it and set it up manually.


## Other features

`vip-go-ci` has support for various features not documented above, such as dismissing stale reviews, setting specific options via the repository being scanned and more. These features are configurable via the command-line or the environment, and are documented below.

Note: To make it easier to read the documention below, some required parameters are not shown (such as `--repo-owner`, `--repo-name`, etc).

### Configuring via environmental variables

If you run `vip-go-ci` in an environment such as `TeamCity` or `GitHub Actions`, it can be useful to configure certain parameters via environmental variables. This way, the parameters are not visible in any logs and cannot be seen in the process-tree during run-time. With `vip-go-ci`, this can easily be done by running it this way:

> ./vip-go-ci.php --commit="$COMMIT_ID" --phpcs=true --lint=true --autoapprove=true --autoapprove-filetypes="css,txt,pdf" --env-options="repo-owner=GH_REPO_OWNER,repo-name=GH_REPO_NAME,token=GH_TOKEN"

In this case, `--repo-owner` will be read from the `$GH_REPO_OWNER` environmental variable, `--repo-name` from `$GH_REPO_NAME`, and so forth. Other parameters are set via the command-line.

Any parameter can be read from the environment, not just those shown. Parameters read from environmental variables are processed and sanitized exactly the same way as parameters directly specified on the command-line. You can configure some parameters from the command-line directly, while others are read from the environment. Parameters configured via the command-line cannot be configured also from the environment; the latter ones will be ignored on run-time.

### Configuration via repository config-file

One option can currently be configured via a repository config-file. This way, users with commit-access to a git repository can influence the behaviour of `vip-go-ci`. The idea is to allow users flexibility in how scanning is performed. Various checks are made to the configuration option read.

Currently, the option that can be specified via repository is `--phpcs-severity`. Any default configuration is overwritten during run-time by the new value, should it be valid. This feature can be enabled or disabled via `--phpcs-severity-repo-options-file`.

To use the feature, make sure a `.vipgoci_options` file can be found at the root of the relevant git-repository, and run `vip-go-ci` like this:

> ./vip-go-ci.php --phpcs-severity-repo-options-file=true 

Should the file not be found, the value not be valid, or altering of the option is not available, the option will not be altered on run-time.

This feature might be extended to other options in the future.

### Informational URL

To help users understand better why a bot is posting comments and reviews on their Pull-Requests, and sometimes automatically approving them, it can be helpful to have a bit of information added to the comments `vip-go-ci` posts. This feature serves this purpose.

To have a URL posted, simply run `vip-go-ci` with a `--informational-url` parameter:

> ./vip-go-ci.php --informational-url=https://myservice.mycompany.is/info-on-scanning

The URL will be included in any generic Pull-Request comments or Pull-Request reviews submitted.

### PHPCS configuration

Support for checking for issues in PHP files by using [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer/) scanning is supported. The behaviour of PHPCS scanning can be configured using several options.

An example of how PHPCS can be used:

> ./vip-go-ci.php --phpcs=true --phpcs-path="$HOME/vip-go-ci-tools/phpcs/bin/phpcs" --phpcs-standard="WordPress-VIP-Go,PHPCompatibilityWP" --phpcs-sniffs-exclude="WordPress.WP.PostsPerPage.posts_per_page_posts_per_page" --phpcs-severity=1 --phpcs-runtime-set="testVersion 7.3-" --phpcs-skip-scanning-via-labels-allowed=true 

With these settings, PHPCS is turned on, is expected to be found in the path shown above, should use two PHPCS standards (`WordPress-VIP-Go` and `PHPCompatibilityWP`), while excluding one particular PHPCS sniff. When executing PHPCS, one runtime option should be set (`testVersion 7.3-`) and severity level should be `1`. Also, users can ask to skip scanning particular Pull-Requests by setting a label named `skip-phpcs-scan`.

Any number of PHPCS standards can be specified, and any number of runtime settings as well. See section above about configuring options via repository file.

### SVG scanning

`vip-go-ci` supports scanning SVG files for dangerous tags. The scanning is accomplished by a [SVG scanner](https://github.com/Automattic/vip-go-svg-sanitizer), while `vip-go-ci` takes care of posting the issues found.

To make use of this feature, the `--svg-checks` and `--svg-scanner-path` options should be used. For example:

> ./vip-go-ci.php --svg-checks=true --svg-scanner-path="$HOME/vip-go-ci-tools/vip-go-svg-sanitizer/svg-scanner.php"

With these options, SVG scanning is turned on and a scanner at a particular path location is to be used. 

### Autoapprovals

### Hashes API

This feature is useful when you want to automatically approve Pull-Requests containing PHP or JavaScript files that are already known to be good and are approved already, so no manual reviewing is needed. To make use of this feature, you will need a database of files already approved. You will also have to be using the auto-approvals feature. 

The feature can be activated using the `--hashes-api` parameter and by specifying a HTTP API endpoint. For instance:

> ./vip-go-ci.php --autoapprove=true --hashes-api=true --hashes-api-url=https://myservice.mycompany.is/wp-json/viphash/

Configured this way, `vip-go-ci` will make HTTP API requests for any PHP or JavaScript file it sees being altereed in Pull-Requests it scans. The HTTP API requests would look like this:

> https://myservice.mycompany.is/wp-json/viphash/v1/hashes/id/[HASH]

where `[HASH]` is a SHA1 hash of a particular PHP or JavaScript file, after it all comments and whitespaces have been removed from them. `vip-go-ci` expectes a JSON result like this from the HTTP API:

```
[{"status":"true"},{"status":"true"}]
```

The JSON result can contain other fields, but they are not used. Note that a single "false" status is enough to make sure a file is considered _not_ approved.

An open-source tool to label files as approved or non-approved is available [here](https://github.com/Automattic/vip-hash/). It requires a HTTP API service that `vip-go-ci` communicates with as well.

### Ignore certain branches

### Skipping certain folders

### Limiting review comments 

### Dismissing stale reviews


### IRC support


## Unittests

To run the unitests for `vip-go-ci`, you will need to install `phpunit` and any dependencies needed (this would include `xdebug`). Then run the unittests using the following command:

> phpunit tests/ -vv

By using this command, you will run the whole test-suite and get feeback on any errors or warnings. 

Note that by default, some tests will be skipped, as these will require a GitHub token to write to GitHub in order to complete, or access to the hashes-to-hashes database. To enable the testing of these, you need to set up a `unittests-secrets.ini` file in the root of the repository. It should include the following fields:

```
[auto-approvals-secrets]
hashes-api-url=
hashes-oauth-consumer-key=
hashes-oauth-consumer-secret=
hashes-oauth-token=
hashes-oauth-token-secret=

[git-secrets]
github-token= ; Personal access token from GitHub
team-id=      ; Team ID to test if present, this is a numeric
team-slug=    ; Team slug to test if present, is a string. Should be referencing the same team as team-id.
org-name=     ; GitHub organisation name to use in testing
```

This file is not included, and needs to be configured manually. When that is complete, the tests can be re-run.

