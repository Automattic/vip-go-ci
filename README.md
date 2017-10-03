# vip-go-ci

Continuous integration for VIP Go repositories.

A PHP-program that can be called for each commit pushed to Pull-Requests on GitHub. For each commit, it will scan the files affected by the commit using PHPCS and PHP Lint, and for any issues outputted by PHPCS or PHP Lint, post a comment on the commit with the issue, in the form of a 'GitHub Review'. If so desired, the program can only do PHPCS scanning, or PHP-linting. It can scan any commit, not just the latest.

## Testing

### On the console

To run this standalone on your local console, PHPCS has to be installed and configured with a certain profile. The `tools-init.sh` script that is included, can be run, and that will place PHPCS along with `vip-go-ci` into `vip-go-ci-tools` in the local home directory.

After the shell-script has been run successfully, `vip-go-ci.php` can be run on your local console to scan a particular commit in a particular repository:

> ./vip-go-ci.php --repo-owner=repo-owner --repo-name=repo-name --commit=commit-ID --token=GitHub-Access-Token --phpcs-path=phpcs-path

-- where `repo-owner` is the GitHub repository-owner, `repo-name` is the name of the repository, `commit-ID` is the SHA-hash identifying the commit, `GitHub-Access-Token` is a access-token created on GitHub that allows reading and commenting on the repository in question, and `path-to-phpcs` is a full path to PHPCS.

The output you see should be something like this:

```
[ 2017-08-10T14:21:30+00:00 ] About to scan repository; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "9bf0208a8bc2b86b43513a5d2c4b78fa1ee9244b"
}
[ 2017-08-10T14:21:30+00:00 ] Fetching commit info from GitHub; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "9bf0208a8bc2b86b43513a5d2c4b78fa1ee9244b"
}
[ 2017-08-10T14:21:32+00:00 ] Fetching comments info from GitHub; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "9bf0208a8bc2b86b43513a5d2c4b78fa1ee9244b"
}
[ 2017-08-10T14:21:34+00:00 ] Fetching file-information from GitHub; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "9bf0208a8bc2b86b43513a5d2c4b78fa1ee9244b",
    "filename": "myfile.php"
}
[ 2017-08-10T14:21:35+00:00 ] About to PHPCS-scan file; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "9bf0208a8bc2b86b43513a5d2c4b78fa1ee9244b",
    "filename": "myfile.php",
    "temp_file_name": "\/tmp\/phpcs-scan-WSAUiB"
}
[ 2017-08-10T14:21:35+00:00 ] About submit a comment to GitHub about an issue; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "9bf0208a8bc2b86b43513a5d2c4b78fa1ee9244b",
    "filename": "myfile.php",
    "position": 3,
    "level": "ERROR",
    "message": "Expected next thing to be an escaping function (see Codex for 'Data Validation'), not 'time'"
}
[ 2017-08-10T14:21:39+00:00 ] Cleaning up, and sleeping a bit (for GitHub); []
[ 2017-08-10T14:21:39+00:00 ] Shutting down; {
    "run_time_seconds": 9
}
```


### Starting a local instance of TeamCity

You can start a local instance of TeamCity in Docker.

```
docker-compose up -d
open http://localhost:8111
```

To start with multiple agents (for example, three):

```
docker-compose up -d --scale agent=3
```

Alternatively, if you do not wish to run TeamCity in a Docker-instance, you can of course download it and set it up manually.

### Configuring TeamCity runner

You can set this up with TeamCity, so that when a commit gets pushed to GitHub, `vip-go-ci.php` will run and scan the commit.

Follow these steps to get it working:

* Create a project, and link it to the GitHub repository you wish to scan
* Create a build-runner by clicking on `Create build configuration` on the project
* Define a build-feature, by clicking on `Add Build Feature` (located in `Build Features`, found in the project-settings). Define the type of the build-feature as `Commit status publisher`, `VCS Root` as `All attached VCS Roots`, and `Publisher` as `GitHub`. 
* Click on `Version Control Settings` (in the project-settings), make sure to do the following:
  - Checkout directory as `Custom path`, and path as something unique and unreadable from other users (local-directory for the build-runner user would be optimal).
  - Click on `Clean all files in the checkout directory before the build`.
* Make sure the build-runner is of the `Command Line` type, that `If all previous steps finished successfully` is chosen, and that `Custom Script` is chosen for the run `Run` field.
* Add a shell-script into the `Custom Script` field, the script should look something like the following:

```
if [ ! -d ~/vip-go-ci-tools ] ; then
	wget https://raw.githubusercontent.com/Automattic/vip-go-ci/master/tools-init.sh -O tools-init.sh && \
	bash tools-init.sh && \
	rm -f tools-init.sh
fi

~/vip-go-ci-tools/vip-go-ci/vip-go-ci.php --repo-owner=... --repo-name=... --commit="$BUILD_VCS_NUMBER"  --token=... --local-git-repo=... --phpcs=true --lint=true  --phpcs-path="$HOME/vip-go-ci-utils/phpcs/bin/phpcs"
```

The parameters should be pretty-much self-explanatory. Note that --commit should be left exactly as shown above, as `$BUILD_VCS_NUMBER` is populated by TeamCity. 

That is it. Now TeamCity should run `vip-go-ci.php` for every incoming commit to any Pull-Request.

