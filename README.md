# Laravel Installer

An alternate version of the Laravel installer that reimplements creating projects using
previous Laravel versions (which was removed from the [original version](https://github.com/laravel/installer)).

### Installation

Install using composer, this will automatically replace the [`laravel/installer`](https://github.com/laravel/installer) package

```
composer global require gerardbalaoro/laravel-installer dev-master
```

### Usage

```
Description:
  Create a new Laravel application

Usage:
  new [options] [--] [<name>]

Arguments:
  name

Options:
      --dev             Installs the latest "development" release
  -f, --force           Forces install even if the directory already exists
      --6.0             Installs the latest "6.0" release
      --5.8             Installs the latest "5.8" release
      --5.7             Installs the latest "5.7" release
      --5.6             Installs the latest "5.6" release
      --5.5             Installs the latest "5.5" release
      --5.4             Installs the latest "5.4" release
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Versioning

This package will retain release numbers the [original package](https://github.com/laravel/installer) uses with a `GB` build metadata appended.
Updates to [`laravel/installer`](https://github.com/laravel/installer) will be merged to this one.



