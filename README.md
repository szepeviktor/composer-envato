# Composer plugin for Envato

[![Packagist](https://img.shields.io/packagist/v/szepeviktor/composer-envato.svg?color=239922&style=popout)](https://packagist.org/packages/szepeviktor/composer-envato)
[![Packagist stats](https://img.shields.io/packagist/dt/szepeviktor/composer-envato.svg)](https://packagist.org/packages/szepeviktor/composer-envato/stats)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-239922)](https://github.com/phpstan/phpstan)

A [Composer plugin](https://getcomposer.org/doc/articles/plugins.md)
to load WordPress [themes](https://themeforest.net/category/wordpress)
and [plugins](https://codecanyon.net/category/wordpress) from [Envato](https://envato.com/).

:bulb: Always the latest version is installed, as Envato does not make other versions available.
Package version locking can only be achieved by local persistent cache, not across hosts or users.

### Installation

This Composer plugin adds a virtual package repository.

It is recommended to install it globally:

```shell
composer global require --update-no-dev szepeviktor/composer-envato
```

It can also be installed per-project:

```shell
composer require --update-no-dev szepeviktor/composer-envato
```

### Configuration

Add all your Envato products as "packages" in either the local `composer.json`
file or the global `config.json` file (located in `$COMPOSER_HOME`).

You can find the `item-id` at the end of product URL-s.
e.g. `https://themeforest.net/item/avada-responsive-multipurpose-theme/2833226`

```json
{
  "config": {
    "envato": {
      "token": "YOUR ENVATO PERSONAL TOKEN FROM https://build.envato.com/create-token",
      "packages": {
        "envato/avada-theme": {
          "item-id": 2833226,
          "type": "wordpress-theme"
        },
        "envato/layerslider-plugin": {
          "item-id": 1362246,
          "type": "wordpress-plugin"
        }
      }
    }
  }
}
```

:bulb: Please use the vendor name `envato` for consistency.

The personal token can also be read from an environment variable or a `.env` file.
Create a `.env` file, where the `composer.json` file lives, and add the following:

```ini
ENVATO_TOKEN="<YOUR ENVATO PERSONAL TOKEN FROM https://build.envato.com/create-token>"
```

### Usage

Once the plugin is installed and configured,
you can simply install any of the listed products as Composer packages.

:bulb: Envato API has [dynamic rate limiting](https://build.envato.com/api/#rate-limit)

### Behind the scenes

1. This package is a Composer plugin
1. In the `activate` method it creates an `ArrayRepository`
   with package data from `config.json`
1. Package version is queried from Envato API
1. When installing a package its URL is also queried from Envato API

- Pretty package version is e.g. `v1.2`
- Normalized package version is e.g. `1.2.0.0`
