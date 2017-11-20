# Welwitschi-Auth

|master|develop|
|:----:|:-----:|
|[![build status](https://gitlab.namibsun.net/namboy94/welwitschi-auth/badges/master/build.svg)](https://gitlab.namibsun.net/namboy94/welwitschi-auth/commits/master)|[![build status](https://gitlab.namibsun.net/namboy94/welwitschi-auth/badges/develop/build.svg)](https://gitlab.namibsun.net/namboy94/welwitschi-auth/commits/develop)|

![Logo](resources/logo/logo.png)

Welwitschi-Auth is an authentication library for use in websites written in
PHP. It offers an object-oriented abstraction over rows stored in a
MySQL/MariaDB database.

Accounts created by this library all receive a unique ID in the database.
Usernames and email addresses are also unique, it is not possible to have
duplicate entries.

Upon creation of an account, the account will need to be confirmed before
being able to log in or generate API keys.

A user is able to log in with exactly one device at a time, as well as have
one active API token.

## Security

Passwords, API Tokens and Login Tokens are all stored salted and hashed
using the builtin `password_hash()` function. The algorithm used to hash the
password is `PASSWORD_BCRYPT`.

All SQL Statements with variable values are properly escaped to avoid SQL
injection.

Usernames and Email addresses are sanitized using html `htmlspecialchars` to
avoid Cross-Site-Scripting attacks (XSS).

## Installation

You can use welwitschi-auth by adding the requirement

    "namboy94/welwitschi-auth": "dev-master"
    
to your `composer.json` file an then running `composer install`. You can then
find the classes in `vendor/namboy94/welwitschi-auth/src`. Thanks to
autoloader, you should be able to easily access the classes from cheetah-bets.

## Documentation

All classes and methods are documented using DocBlock comments. Additional
Documentation can be found in [doc](doc/).

## Further Information

* [Changelog](https://gitlab.namibsun.net/namboy94/welwitschi-auth/raw/master/CHANGELOG)
* [License (GPLv3)](https://gitlab.namibsun.net/namboy94/welwitschi-auth/raw/master/LICENSE)
* [Gitlab](https://gitlab.namibsun.net/namboy94/welwitschi-auth)
* [Github](https://github.com/namboy94/welwitschi-auth)
* [Git Statistics (gitstats)](https://gitstats.namibsun.net/gitstats/welwitschi-auth/index.html)
* [Git Statistics (git_stats)](https://gitstats.namibsun.net/git_stats/welwitschi-auth/index.html)
* [Test Coverage](https://coverage.namibsun.net/welwitschi-auth/index.html)
* [Packagist Page](https://packagist.org/packages/namboy94/welwitschi-auth)
