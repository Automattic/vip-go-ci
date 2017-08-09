# vip-go-ci

Continuous integration for VIP Go repositories.

A PHP-program that can be called for each commit made on GitHub For each commit, it will scan the files affected by the commit using PHPCS, and for any issues outputted by PHPCS, post a comment on the commit, containing the issue.

