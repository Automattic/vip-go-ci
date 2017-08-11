# vip-go-ci

Continuous integration for VIP Go repositories.

A PHP-program that can be called for each commit made on GitHub. For each commit, it will scan the files affected by the commit using PHPCS, and for any issues outputted by PHPCS, post a comment on the commit, containing the issue.

## Testing

### On the console

To run this standalone on your local console, PHPCS has to be installed and configured with a certain profile at a certain path. To get the profile installed, the following shell-script can be run:

```
if [ ! -d ~/phpcs-scan ] ; then
	TMP_FOLDER=`mktemp -d /tmp/phpcs-scan-XXXXXX`

	cd $TMP_FOLDER && \
	wget https://github.com/squizlabs/PHP_CodeSniffer/archive/2.8.0.tar.gz && \
	tar -zxvf 2.8.0.tar.gz  && \
	rm -fv 2.8.0.tar.gz && \
	mv PHP_CodeSniffer-2.8.0/ phpcs && \
	wget https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/archive/0.11.0.tar.gz && \
	tar -zxvf 0.11.0.tar.gz  && \
	rm -fv 0.11.0.tar.gz && \
	mv WordPress-Coding-Standards-0.11.0/WordPress* phpcs/CodeSniffer/Standards/ && \
	git clone -b master https://github.com/Automattic/VIP-Coding-Standards.git VIP-Coding-Standards && \
	mv VIP-Coding-Standards/WordPressVIPMinimum/ phpcs/CodeSniffer/Standards/  && \
	git clone -b master https://github.com/Automattic/vip-go-ci.git && \
	mv $TMP_FOLDER ~/phpcs-scan && \
	echo "Installation finished"
fi
```

This should only need to be run once.

After the shell-script has been run successfully, `phpcs-scan.php` can be run on your local console to scan a particular commit in a particular repository:

> ./phpcs-scan.php repo-owner repo-name commit-ID GitHub-Access-Token

-- were `repo-owner` is the GitHub repository-owner, `repo-name` is the name of the repository, `commit-ID` is the SHA-hash identifying the commit, and `GitHub-Access-Token` is a access-token created on GitHub that allows reading and commenting on the repository in question.

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

You can set this up with TeamCity, so that when a commit gets pushed to GitHub, `phpcs-scan.php` will run and scan the commit.

Follow these steps to get it working:

* Create a project, and link it to the GitHub repository you wish to scan
* Create a build-runner by clicking on `Create build configuration` on the project
* Make sure the build-runner is of the `Command Line` type, that `If all previous steps finished successfully` is chosen, and that `Custom Script` is chosen for the run `Run` field.
* Add a shell-script into the `Custom Script` field. The shell-script should be the one shown in the previous section on how to run `phpcs-scan.php` on the console -- adding this will make sure all the tools `phpcs-scan.php` needs are set up automatically on your build-runner instances
* In addition, the `Custom Script` field should contain, at the absolute bottom, the following: `~/phpcs-scan/vip-go-ci/phpcs-scan.php repo-owner repo-name "$BUILD_VCS_NUMBER" GitHub-Access-Token`. `repo-owner` etc need to be replaced, in the same way as shown above in the local console example. `$BUILD_VCS_NUMBER` should be left untouched, as that is provided by TeamCity on execution.


