# vip-go-ci

Continuous integration for VIP Go repositories.

A PHP-program that can be called for each commit made on GitHub. For each commit, it will scan the files affected by the commit using PHPCS, and for any issues outputted by PHPCS, post a comment on the commit, containing the issue.

## Testing

### On the console

To run this standalone on your local console, PHPCS has to be installed and configured with a certain profile at a certain path. To get the profile installed, the following shell-script can be run:

```
if [ ! -d ~/php-validation ] ; then
	TMP_FOLDER=`mktemp -d /tmp/php-validation-XXXXXX`

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
	mv $TMP_FOLDER ~/php-validation && \
	echo "Installation finished"
fi
```

Note that `~/php-validation` is assumed to exist by `phpcs-scan.php`.

After the shell-script has been run successfully, `phpcs-scan.php` can be run on your local console:

> ./phpcs-scan.php [repo-owner] [repo-name] [commit-ID] [GitHub-Access-Token]

-- were repo-owner is the GitHub repository-owner, repo-name is the name of the repository, commit-ID is the SHA-hash identifying the commit, and GitHub-Access-Token is a access-token created on GitHub that allows reading and commenting on the repository in question.

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
    "filename": "bla-2.php"
}
[ 2017-08-10T14:21:35+00:00 ] About to PHPCS-scan file; {
    "repo_owner": "mygithubuser",
    "repo_name": "testing123",
    "commit_id": "9bf0208a8bc2b86b43513a5d2c4b78fa1ee9244b",
    "filename": "bla-2.php",
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


### In Docker

You can start a local instance of TeamCity in Docker.

```
docker-compose up -d
open http://localhost:8111
```

To start with multiple agents (for example, three):

```
docker-compose up -d --scale agent=3
```


