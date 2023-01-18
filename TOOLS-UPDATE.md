# Updating tools-init.sh with new versions

[tools-init.sh](tools-init.sh) will install PHPCS and related tools in your home-directory upon execution. It will keep these tools up to date when run; it should be executed on regular basis to keep everything up to date (see [installation section](README.md#installing)).

However, once a while `tools-init.sh` itself needs to be updated with new versions of these utilities. The file keeps three data entries for each utility: Repository owner and name for the utility, version number, and SHA1 hash. The repository owner and name refers to GitHub repository, the version number refers to a release number on GitHub, and the hash to the SHA1 hash of the release's `.tar.gz` archive on GitHub. The hash is used to make sure that the relevant utility has not changed since last updated in `tools-init.sh`.

Only version number and SHA1 hash need updating for a new release of a utility. These can be determined in the following way. Releases of the `WordPress-Coding-Standards` utility, for instance, are hosted [here](https://github.com/WordPress/WordPress-Coding-Standards/releases). Once a version has been chosen, `tools-init.sh` can be updated in the following way:

```
export WP_CODING_STANDARDS_VER="2.1.1"
```

Then the hash has to be calculated. First, obtain a `.tar.gz` archive for the release from GitHub. The download URL for `WordPress-Coding-Standards` is: `https://github.com/WordPress/WordPress-Coding-Standards/archive/VERSION.tar.gz` -- simply replace `VERSION` with the version to be used. Then run the `sha1sum` UNIX utility against the downloaded file. Any other compatible tool can be used. 

For version 2.1.1 of `WordPress-Coding-Standards` the hash is added as follows:

```
export WP_CODING_STANDARDS_SHA1SUM="d35ec268531453cbf2078c57356e38c5f8936e87";
```

All utilities in `tools-init.sh` follow the same pattern. Utility updates in the file should be committed to the repository via pull requests. 

