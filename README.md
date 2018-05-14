# vip-go-ci

Continuous integration for VIP Go repositories.

`vip-go-ci` is a PHP-program that can be called for latest commits pushed to Pull-Requests on GitHub, looking for problems in the code using PHP lint and PHPCS, and posting back to GitHub comments and reviews, detailing the issues found.

`vip-go-ci` is to be called from the commandline, using several arguments specifying the repository, commit-ID, and other options. The program will then scan the code. The program expects a fully-functional git-repository to be stored locally on the computer running it, were from it can extract various information.

The program has different behaviours for different scanning-methods. For PHP linting, it will loop through every file existing in the code-base, and post a generic Pull-Request comment for any issues it finds with the PHP-code. In case of PHPCS scanning, however, it will scan only the files affected by the Pull-Request using PHPCS, and for any issues outputted by PHPCS, post a comment on the commit with the issue, in the form of a 'GitHub Review' (this includes inline comments and a review-message).

If so desired, the program can only do PHPCS scanning, or PHP-linting. It should be called only for the latest commit pushed.

This program comes with a small utility, `tools-init.sh`, that will install PHPCS and related tools in your home-directory upon execution. This utility will check if the tools required are installed, and if not, install them, or if they are, check if they are of the latest version, and upgrade them as needed.

## Setting up

### On the console, standalone

It is possible to run `vip-go-ci` standalone on the console, although it has limited functionality. It is mainly useful for debugging purposes and to understand if everything is correctly configured.

To run this standalone on your local console, PHPCS has to be installed and configured with a certain profile. The `tools-init.sh` script that is included will install the tools needed.

After the tools have been installed, `vip-go-ci.php` can be run on your local console to scan a particular commit in a particular repository:

> ./vip-go-ci.php --repo-owner=`repo-owner` --repo-name=`repo-name` --commit=`commit-ID` --token=`GitHub-Access-Token` --phpcs-path=`phpcs-path` --phpcs=true --lint=true

-- where `repo-owner` is the GitHub repository-owner, `repo-name` is the name of the repository, `commit-ID` is the SHA-hash identifying the commit, `GitHub-Access-Token` is a access-token created on GitHub that allows reading and commenting on the repository in question, and `path-to-phpcs` is a full path to PHPCS.

The output from `vip-go-ci` you will get should be something like this:

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

You can set up `vip-go-ci` with TeamCity, so that when a commit gets pushed to GitHub, `vip-go-ci.php` will run and scan the commit.

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

Note that the script has built-in commands to install all the utilities `vip-go-ci` relies on, so that they will be configured automatically, and maintained automatically as well.

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


