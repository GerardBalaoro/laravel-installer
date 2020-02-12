# Laravel Installer

An alternate version of the Laravel installer that reimplements creating projects using
previous Laravel versions (which was removed from the [original version](https://github.com/laravel/installer)).

### Installation

Install using composer, this will automatically replace the [`laravel/installer`](https://github.com/laravel/installer) package

```
composer global require gerardbalaoro/laravel-installer dev-master
```

### Usage

* **`laravel new [options] [--] [<name> [<version>]]`**
  * Create a new Laravel application
  ```
  Arguments:
    name
    version               Install specified Laravel version

  Options:
        --dev             Installs the latest "development" release
        --auth            Installs the Laravel authentication scaffolding
    -f, --force           Forces install even if the directory already exists
  ```
* **`laravel cache [options] [--] [<versions>...]`**
  * Download Laravel packages to cache
  ```
  Arguments:
    versions              Versions to download [default: ["master"]]

  Options:
    -a, --all             Downloads all releases
        --clean           Cleans cache directory
  ```
* **`laravel versions`**
  * Show available Laravel versions

#### Example

To create a new Laravel 5.8 project, run the command:
  ```bash
  $ laravel new blog 5.8
  ```

### Caching

This package automatically caches the Laravel package every time the `new` command is executed.
This is very useful when creating projects while offline.

### Updates

Updates to [`laravel/installer`](https://github.com/laravel/installer) will be merged to this one.



